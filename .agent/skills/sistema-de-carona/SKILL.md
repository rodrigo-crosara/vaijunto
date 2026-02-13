# SKILL: Build VaiJunto MVP (Carpool PWA) - Lite Edition

## 1. Project Overview & Philosophy
**Project:** "VaiJunto" - A Frictionless Carpooling PWA.
**Goal:** Create a "Native-App like" experience using web technologies. Lightweight, fast, and routine-centric.
**Core Principles:**
1.  **Zero Friction:** Login via phone number. No passwords.
2.  **Lite Architecture:** No heavy local frameworks. Pure Tailwind CSS (CDN) + jQuery + SweetAlert2.
3.  **Visual Identity:** Glassmorphism, "Puffy" Cards (`rounded-[2.5rem]`), Inter Font, Modern/SaaS aesthetic.
4.  **Role Segregation:** Distinct experiences for Drivers (Offer/Manage) vs. Passengers (Search/Book).

## 2. Technical Stack
- **Frontend Core:** HTML5 + Tailwind CSS (v3.4+ via CDN).
- **Interactivity:** jQuery 3.6+ (CDN) + SweetAlert2 (CDN).
- **Icons/Fonts:** Bootstrap Icons (CDN) + Google Fonts (Inter).
- **Backend:** PHP 8.3 (Native/Vanilla) - Stateless API.
- **Database:** MySQL 8.0 (PDO Connection).
- **Platform:** PWA (Progressive Web App).

---

## 3. Phase 0: Workspace & Environment Setup
**Objective:** Create a clean, dependency-free structure.
**Agent Instructions:**
1.  **Directory Structure:**
    - `/api` (PHP logic endpoints)
    - `/config` (DB connection `db.php`)
    - `/views` (HTML fragments: `login.php`, `feed.php`, `my_rides.php`, etc.)
    - `/includes` (Partials: `header.php`, `nav.php`, `footer.php`)
    - `/assets/media` (User uploads & static icons)
2.  **CDN Configuration (`includes/header.php`):**
    - Import Tailwind CSS.
    - Configure Tailwind Theme: `colors: { primary: '#009EF7' }`, `fontFamily: { sans: ['Inter'] }`.
    - Import Bootstrap Icons & Google Fonts.
    - **NO** local Metronic CSS/JS files.

---

## 4. Phase 1: Database Schema (Consolidated)
**Agent Instructions:** Ensure `vaijunto_db` has these exact definitions:

1.  **`users`**
    - `id`, `phone`, `name`, `photo_url`, `bio`, `reputation`, `created_at`.
    - **`is_driver`** (BOOLEAN/TINYINT Default 0).
    - **`pix_key`** (VARCHAR(100)).

2.  **`cars`**
    - `id`, `user_id`, `model`, `color`, `plate`, `photo_url`.

3.  **`rides`** (The Offer)
    - `id`, `driver_id`, `origin_text`, `destination_text`, `departure_time`, `price`.
    - `seats_total`, `seats_available`.
    - **`waypoints`** (JSON) - *Critical for Smart Search*.
    - `details` (TEXT) - *For specific notes*.
    - `status` ('active', 'finished', 'canceled').

4.  **`bookings`** (The Match)
    - `id`, `ride_id`, `passenger_id`, `created_at`.
    - `status` ('confirmed', 'rejected', 'canceled').
    - **`meeting_point`** (VARCHAR 255) - *Selected from waypoints*.
    - **`payment_status`** ('pending', 'paid').

---

## 5. Phase 2: Core Features & Logic

### A. Authentication & Onboarding
- **Phone Login:** Zero friction. Creates account if phone doesn't exist.
- **Profile Enforcer:** Middleware in `index.php`. If `photo_url` or `name` is empty -> Redirect to Profile Edit.
- **Role Switching:** Session variable `$_SESSION['is_driver']` controls UI visibility.

### B. Smart Search (Feed)
- **Logic:** `api/search_rides.php` queries `origin`, `destination`, AND `waypoints` JSON.
- **Debounce:** Trigger search 500ms after typing.
- **Visuals:** Skeleton loading state. "Badge" indication if the match is on a waypoint.
- **Real-time:** Polling every 15s to check for new rides.

### C. Booking & Payment (In-Ride Mode)
- **Booking Flow:** SweetAlert Dropdown to select `meeting_point`.
- **In-Ride Card:** If a confirmed booking exists for Today (Time Window: -2h to +4h):
    - Show "Active Ride Card" at top of Feed.
    - Button: "Copy Pix" (Copies driver's `pix_key`).
    - Button: "Pay Cash" (Triggers "Exact Change" warning).
- **Driver Control:** Driver can mark passenger as "Paid" (Green Check) in `my_rides.php`.

### D. Driver Tools
- **Smart Return:** Button to invert Origin/Dest + 9 hours.
- **Manage Ride:** Edit seats, cancel ride, view passenger list (with photos).

---

## 6. Phase 3: Modern UI/UX Guidelines (Glass & Lite)

### 1. The "Glass" Navigation
- **Bottom Nav:** `fixed bottom-0 w-full bg-white/90 backdrop-blur-md border-t-0`.
- **Floating Button:** For drivers, the central (+) button floats above the bar (`-mt-8`).

### 2. The "Puffy" Card Design
- **Container:** `bg-white rounded-[2rem] shadow-sm p-5 mb-4 border border-slate-50`.
- **Typography:** Strong contrast. Time in `font-black`, locations in `font-medium text-gray-600`.
- **Timeline:** Dotted vertical line (`border-l-2 border-dashed border-gray-300`) connecting start/end dots.

### 3. Inputs & Forms
- **Style:** "SaaS Modern". `bg-gray-50 border-0 rounded-2xl focus:ring-2 ring-primary/20`.
- **Buttons:** High saturation primary color with colored shadows (`shadow-lg shadow-blue-500/30`).

---

## 7. Execution Sequence
1.  **CLEANUP:** Wipe local assets. Setup CDNs.
2.  **DB:** Update schema with new columns (`waypoints`, `pix`, `meeting_point`).
3.  **UI REFACTOR:** Rewrite `login.php`, `feed.php`, `nav.php` using pure Tailwind.
4.  **LOGIC:** Implement Smart Search, Role Segregation, and In-Ride context.
5.  **POLISH:** Apply Glassmorphism and animations.