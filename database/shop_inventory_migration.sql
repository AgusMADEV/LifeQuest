-- Inventario y equipamiento de cosmeticos de tienda

CREATE TABLE IF NOT EXISTS user_reward_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_id INT NOT NULL,
    equipped BOOLEAN NOT NULL DEFAULT FALSE,
    acquired_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    equipped_at DATETIME NULL,
    UNIQUE KEY unique_user_reward_inventory (user_id, reward_id),
    INDEX idx_user_reward_inventory_equipped (user_id, equipped),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE
);

INSERT IGNORE INTO user_reward_inventory (user_id, reward_id, equipped, acquired_at)
SELECT rr.user_id,
       rr.reward_id,
       0,
       MIN(rr.redeemed_at)
FROM reward_redemptions rr
INNER JOIN rewards r ON r.id = rr.reward_id
WHERE r.shop_type = 'cosmetic'
GROUP BY rr.user_id, rr.reward_id;
