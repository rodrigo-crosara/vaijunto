# SKILL: Build VaiJunto MVP (Carpool PWA) - Lite Edition

## 1. Project Overview
**Project:** "VaiJunto" - Frictionless Carpooling PWA.
**Stack:** PHP 8.3 (Native) + Tailwind CSS (CDN) + jQuery (CDN).
**Philosophy:** Mobile-first, Speed, Zero Friction.

## 2. Critical Rules (DO NOT BREAK)
1.  **NO Local Libraries:** Do NOT download `metronic`, `bootstrap`, `tinymce`, or `vendors`. Use **CDNs only**.
2.  **Phone Auth Only:** Users login with a Phone Number. No E-mail. No Passwords.
3.  **Tailwind UI:** Build UI using utility classes (e.g., `bg-white rounded-2xl shadow-sm`). Do not use pre-compiled CSS files.

## 3. Database Schema (MySQL)
* **`users`**: `id`, `phone` (Unique), `name`, `photo_url`, `is_driver` (0/1), `pix_key`.
* **`rides`**: `id`, `driver_id`, `origin_text`, `destination_text`, `departure_time`, `price`, `waypoints` (JSON), `seats_available`.
* **`bookings`**: `id`, `ride_id`, `passenger_id`, `status`, `payment_status`.

## 4. Feature Checklist
* [x] **Auth:** Login via Phone (Auto-register).
* [ ] **Onboarding:** Force Name/Photo update if empty.
* [ ] **Roles:** Toggle "I am a Driver" in Profile.
* [ ] **Feed:** Smart Search (Origin/Dest/Waypoints).
* [ ] **Ride:** Create Ride (with Waypoints) & "Smart Return" (Invert route).
* [ ] **Booking:** Select Meeting Point -> WhatsApp Redirect.
* [ ] **In-Ride:** "Pay Now" card (Pix Copy/Paste) on feed when ride is active.

## 5. Directory Structure
* `/api` (JSON endpoints)
* `/views` (HTML content fragments)
* `/includes` (Header/Nav/Footer with CDNs)
* `/assets` (Only custom images/uploads)