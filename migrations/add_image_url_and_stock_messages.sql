-- Migration to add image_url and stock_messages columns to products table

ALTER TABLE products
ADD COLUMN IF NOT EXISTS image_url TEXT;

ALTER TABLE products
ADD COLUMN IF NOT EXISTS stock_messages TEXT;
