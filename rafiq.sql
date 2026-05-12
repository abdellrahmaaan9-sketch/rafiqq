-- =====================================================
-- PASTE THIS IN pgAdmin > Query Tool > Run (F5)
-- This only ADDS columns to your existing tables
-- It does NOT delete or change anything
-- =====================================================

-- Add status to provider table (pending / accepted / rejected)
ALTER TABLE public.provider 
ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS admin_note TEXT,
ADD COLUMN IF NOT EXISTS phone VARCHAR(20),
ADD COLUMN IF NOT EXISTS gender VARCHAR(10),
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT NOW();

-- Add status to place table (active / pending / hidden)
ALTER TABLE public.place 
ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'active',
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT NOW();

-- Set all providers who already have bookings as accepted
UPDATE public.provider 
SET status = 'accepted' 
WHERE user_id IN (SELECT DISTINCT provider_id FROM public.booking WHERE provider_id IS NOT NULL);