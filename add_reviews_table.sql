-- Reviews table for marketplace seller ratings
CREATE TABLE IF NOT EXISTS reviews (
    review_id   SERIAL PRIMARY KEY,
    seller_id   INT NOT NULL,
    reviewer_id INT NOT NULL,
    listing_id  INT REFERENCES listings(id) ON DELETE SET NULL,
    rating      SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    -- One review per buyer per listing (not per seller globally)
    UNIQUE (seller_id, reviewer_id, listing_id),
    FOREIGN KEY (seller_id)   REFERENCES accounts(account_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES accounts(account_id) ON DELETE CASCADE
);
