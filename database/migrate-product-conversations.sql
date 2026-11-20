-- Run once on a database that already has the earlier order-linked messages table.
USE balangkare_market;

CREATE TABLE conversations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  buyer_id INT UNSIGNED NOT NULL,
  farmer_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_conversations_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_conversations_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_conversations_farmer FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_conversation_product_buyer (product_id, buyer_id),
  INDEX idx_conversations_farmer (farmer_id, updated_at)
) ENGINE=InnoDB;

ALTER TABLE messages
  ADD COLUMN conversation_id INT UNSIGNED NULL AFTER id;

INSERT INTO conversations(product_id,buyer_id,farmer_id,created_at,updated_at)
SELECT o.product_id,o.buyer_id,o.farmer_id,MIN(m.created_at),MAX(m.created_at)
FROM orders o
JOIN messages m ON m.order_id=o.id
GROUP BY o.product_id,o.buyer_id,o.farmer_id;

UPDATE messages m
JOIN orders o ON o.id=m.order_id
JOIN conversations c ON c.product_id=o.product_id AND c.buyer_id=o.buyer_id
SET m.conversation_id=c.id;

ALTER TABLE messages
  MODIFY conversation_id INT UNSIGNED NOT NULL,
  DROP FOREIGN KEY fk_messages_order,
  DROP INDEX idx_messages_conversation,
  DROP COLUMN order_id,
  ADD CONSTRAINT fk_messages_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  ADD INDEX idx_messages_conversation (conversation_id,id);
