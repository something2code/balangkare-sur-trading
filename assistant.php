<?php
require __DIR__ . '/includes/bootstrap.php';

function farmbot_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function farmbot_public_listings(PDO $pdo): array
{
    $q = $pdo->query("SELECT id,name,category,price,unit,quantity,location FROM products WHERE status='active' AND quantity>0 ORDER BY created_at DESC LIMIT 20");
    return array_map(static function (array $product): array {
        return [
            'listing_id' => (int)$product['id'],
            'name' => $product['name'],
            'category' => $product['category'],
            'price_per_unit_php' => number_format((float)$product['price'], 2, '.', ''),
            'unit' => $product['unit'],
            'available_quantity' => (float)$product['quantity'],
            'location' => $product['location'],
        ];
    }, $q->fetchAll());
}

function farmbot_extract_answer(array $response): string
{
    $content = $response['choices'][0]['message']['content'] ?? '';
    if (is_string($content)) return trim($content);
    if (!is_array($content)) return '';

    $answer = '';
    foreach ($content as $part) {
        if (is_array($part) && is_string($part['text'] ?? null)) $answer .= $part['text'];
    }
    return trim($answer);
}

function farmbot_api_key(array $config): string
{
    $apiKey = trim((string)($config['openrouter_api_key'] ?? ''));
    if ($apiKey === '') {
        $environmentKey = getenv('OPENROUTER_API_KEY');
        if (is_string($environmentKey)) $apiKey = trim($environmentKey);
    }
    return $apiKey;
}

function farmbot_provider_error_details(string $response): array
{
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) return [];

    $error = is_array($decoded['error'] ?? null) ? $decoded['error'] : $decoded;
    $details = [
        'code' => is_int($error['code'] ?? null) ? $error['code'] : null,
        'status' => is_string($error['status'] ?? null) ? $error['status'] : null,
        'message' => is_string($error['message'] ?? null) ? $error['message'] : null,
    ];

    // Redact key-shaped strings and cap the log entry in case an upstream response is unexpectedly large.
    if ($details['message'] !== null) {
        $details['message'] = preg_replace('/(?:sk-or-v1-|AIza)[0-9A-Za-z_-]{20,}/', '[REDACTED_API_KEY]', $details['message']);
        $details['message'] = mb_substr($details['message'], 0, 500);
    }
    return array_filter($details, static fn($value): bool => $value !== null && $value !== '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) farmbot_json_response(['answer' => 'I could not read that question. Please try again.'], 400);
    $question = is_string($body['question'] ?? null) ? trim($body['question']) : '';
    if ($question === '' || mb_strlen($question) > 1000) {
        farmbot_json_response(['answer' => 'Please enter a question between 1 and 1,000 characters.'], 422);
    }

    $apiKey = farmbot_api_key($config);
    if ($apiKey === '') {
        farmbot_json_response(['answer' => 'FarmBot AI is not configured yet. Add the OpenRouter API key to private server configuration, then try again.'], 503);
    }
    if (!function_exists('curl_init')) {
        farmbot_json_response(['answer' => 'FarmBot cannot connect because PHP cURL is not enabled on this server.'], 503);
    }

    $history = [];
    foreach (array_slice(is_array($body['history'] ?? null) ? $body['history'] : [], -8) as $turn) {
        if (!is_array($turn) || !in_array($turn['role'] ?? '', ['user', 'assistant'], true) || !is_string($turn['content'] ?? null)) continue;
        $content = trim($turn['content']);
        if ($content !== '') $history[] = ['role' => $turn['role'], 'content' => mb_substr($content, 0, 1000)];
    }

    $context = '';
    if (preg_match('/(buy|buyer|product|market|price|rice|grain|seed|fertiliz|vegetable|available|recommend|listing)/iu', $question)) {
        try {
            $listings = farmbot_public_listings($pdo);
            $context = "\n\nCurrent public marketplace listings (these can change):\n" . json_encode($listings, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (PDOException $e) {
            error_log('FarmBot could not load public listings from the database.');
        }
    }

    $systemInstruction = 'You are FarmBot, the helpful AI assistant for Balangkare Sur Trading, a marketplace connecting farmers and buyers in Balangkare, Nueva Ecija. Reply in clear, friendly, concise language. Help with how to use the marketplace, buying and selling agricultural products, and general farming questions. For product availability, prices, and recommendations, use only the current public marketplace listings included in the prompt; never invent listings, prices, stock, or seller details. Explain that listings can change. The app records order requests but does not take payment; buyers and farmers arrange payment and fulfillment themselves. You do not have live weather, soil, crop disease diagnosis, or guaranteed local agricultural advice. For planting or crop treatment decisions, give general information only, avoid promising yield or profit, and recommend confirming with the local Municipal Agriculture Office or an agricultural technician. Do not ask for or repeat passwords, API keys, private addresses, phone numbers, payment credentials, or other sensitive personal information. If asked for information outside your knowledge or the supplied listings, say so plainly.';
    $messages = array_merge(
        [['role' => 'system', 'content' => $systemInstruction]],
        $history,
        [['role' => 'user', 'content' => $question . $context]]
    );
    $requestBody = json_encode([
        'model' => $config['openrouter_model'] ?? 'openrouter/free',
        'messages' => $messages,
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($requestBody)) {
        farmbot_json_response(['answer' => 'FarmBot could not prepare the request. Please try again.'], 500);
    }

    $curl = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $requestBody,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . $apiKey],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 30,
    ]);
    $providerResponse = curl_exec($curl);
    $providerStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_errno($curl);
    curl_close($curl);

    if ($providerResponse === false || $curlError !== 0) {
        error_log('FarmBot OpenRouter connection failed. cURL error number: ' . $curlError);
        farmbot_json_response(['answer' => 'FarmBot could not reach OpenRouter right now. Please try again shortly.'], 502);
    }
    $decodedResponse = json_decode($providerResponse, true);
    if ($providerStatus < 200 || $providerStatus >= 300 || !is_array($decodedResponse)) {
        $providerError = farmbot_provider_error_details($providerResponse);
        error_log('FarmBot OpenRouter API error: ' . json_encode([
            'http_status' => $providerStatus,
            'provider' => $providerError,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        $message = $providerStatus === 401 || $providerStatus === 403
            ? 'OpenRouter did not accept the server API key. Check the key in private configuration.'
            : ($providerStatus === 429 ? 'FarmBot has reached an OpenRouter usage limit. Please wait and try again.'
                : ($providerStatus === 400 ? 'OpenRouter rejected FarmBot’s request. Check the model and request details in the server log.'
                    : ($providerStatus === 404 ? 'The configured OpenRouter model or endpoint was not found. Check the model name in private configuration.'
                        : ($providerStatus === 402 ? 'OpenRouter requires credits for this request or model. Check your OpenRouter account and selected model.'
                            : ($providerStatus === 503 ? 'OpenRouter or its selected model is temporarily unavailable. Please try again shortly.'
                                : 'FarmBot could not get an answer from OpenRouter (HTTP ' . $providerStatus . '). Check the server log for details.')))));
        farmbot_json_response(['answer' => $message], 502);
    }
    $answer = farmbot_extract_answer($decodedResponse);
    if ($answer === '') {
        farmbot_json_response(['answer' => 'FarmBot received an empty answer. Please try asking another way.'], 502);
    }
    farmbot_json_response(['answer' => $answer]);
}

$pageTitle = 'FarmBot';
require __DIR__ . '/includes/header.php';
?>
<section class="page-intro"><span class="eyebrow" style="color:var(--green)">FarmBot</span><h1 style="font-size:clamp(2.1rem,4vw,3.4rem)">A helpful place to start.</h1><p>Ask about using the market, current listings, or general crop-planning questions.</p></section>
<section class="panel chat-panel"><div class="chat-feed" id="assistant-feed"><div class="chat-bubble">Hello! I’m FarmBot. I can help you use the marketplace, explore current listings, and discuss general farming questions. What would you like to know?</div></div><form class="chat-form" id="assistant-form" data-farmbot-form data-feed="assistant-feed"><input name="question" maxlength="1000" placeholder="Example: What rice is available?" aria-label="Your question" required><button class="button" type="submit">Ask</button></form></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
