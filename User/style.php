<?php
$config = include __DIR__ . '/../Admin/theme_config.php';

header("Content-Type: text/css");

$background = $config['background_color'] ?? '#f5f5f5';
$primary    = $config['primary_color'] ?? '#d22e2e';
$secondary  = $config['secondary_color'] ?? '#ffffff';
?>

:root {
    --background-color: <?= htmlspecialchars($background) ?>;
    --primary-color: <?= htmlspecialchars($primary) ?>;
    --secondary-color: <?= htmlspecialchars($secondary) ?>;
}


/* =========================
   GENERAL
========================= */

body {
    background-color: var(--background-color);
}


/* =========================
   TOP NAVIGATION
========================= */

.top-navbar {
    background-color: var(--background-color);
    border-bottom: 2px solid var(--primary-color);
}

.top-nav-button {
    color: var(--primary-color);
    background-color: var(--background-color);
}

.top-nav-button:hover {
    background-color: var(--primary-color);
    color: var(--background-color);
    border: 2px solid var(--primary-color);
}


/* =========================
   SIDEBAR
========================= */

.navbar {
    background-color: var(--primary-color);
    color: var(--secondary-color);
}

.navbar a {
    background-color: var(--primary-color);
    color: var(--secondary-color);
}

.navbar a:hover,
.navbar a.active {
    background-color: var(--secondary-color);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}


/* =========================
   SIDEBAR TOGGLE
========================= */

#toggleBtn {
    background-color: var(--primary-color);
    color: var(--secondary-color);
}

#toggleBtn:hover {
    background-color: var(--secondary-color);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}


/* =========================
   FOOTER
========================= */

.site-footer {
    background-color: var(--primary-color);
    color: var(--secondary-color);
}

.footer-column a {
    color: var(--secondary-color);
}

.footer-column a:hover {
    color: var(--primary-color);
    background-color: var(--secondary-color);
    border: 2px solid var(--primary-color);
}


/* =========================
   NORMAL PRIMARY BUTTONS
========================= */

.primary-btn {
    background-color: var(--primary-color);
    color: var(--secondary-color);
    border: 1px solid var(--primary-color);
}

.primary-btn:hover {
    background-color: var(--secondary-color);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}


/* NORMAL LINKS */

a {
    color: var(--primary-color);
}

a:hover {
    color: var(--primary-color);
}

/* LOG-OUT BUTTON */
.navbar a.logout-button {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}

.navbar a.logout-button:hover {
  background-color: var(--secondary-color);
  color: var(--primary-color);
  border: 2px solid var(--primary-color);
}

/* CAROUSEL AT HOME */
.carousel-section {
  background-color: var(--background-color);
  color: var(--primary-color);
}

/* FOOD SECTION */
.food-features {
  background-color: var(--background-color);
  color: var(--primary-color);
}

.food-text {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}

/* PROFILE PAGE */
.profile-card {
    background-color: var(--background-color);
    border: 2px solid var(--primary-color);
}

.profile-info h1 {
  color: var(--primary-color);
}

.profile-info p {
  color: var(--primary-color);
}

.profile-info p input {
    color: var(--primary-color);
}

.information {
  color: var(--primary-color);
}

.edit-btn {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}

.edit-btn:hover {
  background-color: var(--secondary-color);
	color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

input {
  border: 1px solid #ddd;
  background-color: white;
}

input:focus {
  border-color: var(--primary-color);
  background-color: var(--secondar-color);
}

label {
  color: #aaa;
  background: white;
}

input::placeholder {
  color: #aaa;
  transition: opacity 0.3s ease;
}

input:not(:placeholder-shown) + label,
input:focus + label {
  color: var(--primary-color);
}


/* Ensure text fields fit within the container */
input, select {
  border: 1px solid #ddd;
}

.submit-btn {
  background-color: var(--primary-color);
  color: white;
}

.submit-btn:hover {
  background-color: var(--secondary-color);
  color: var(--primary-color);
  border: 2px solid var(--primary-color);
}

/* ADD MODAL */
.modal-content {
    background: var(--background-color);
}

.modal-header h2 {
    color: var(--primary-color);
}

.confirmpass-modal {
    background: var(--background-color);
}

.confirmpass-modal h3 {
    color: var(--primary-color);
}

#confirm-confirmpass {
    background-color: var(--primary-color);
    color: var(--secondary-color);
}

#confirm-confirmpass:hover {
    background-color: var(--secondary-color);
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
}


/* FORM GROUP / INPUTS */

.form-group {
    position: relative;
    margin-bottom: 15px;
}

input,
select {
    background-color: var(--secondary-color);
}

input:focus {
    border-color: var(--primary-color);
    background-color: #fff;
}

label {
    color: #aaa;
    background: var(--background-color);;
}

input::placeholder {
    color: #aaa;
}

input:not(:placeholder-shown) + label,
input:focus + label {
    color: var(--primary-color);
}


/* SUBMIT BUTTON */

.submit-btn {
    background-color: var(--primary-color);
    color: var(--background-color);;
}

.submit-btn:hover {
    background-color: var(--secondary-color);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.changepass-modal {
    background: var(--background-color);
}

.changepass-modal h3 {
    color: var(--primary-color);
}


#confirm-changepass {
    background-color: var(--primary-color);
    color: var(--secondary-color);
}

#confirm-changepass:hover {
    background-color: var(--secondary-color);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.confirm-email-btn {
    background-color: var(--primary-color);
    color: var(--secondary-color);
    border: none;
    cursor: pointer;
}

.confirm-email-btn:hover {
    background-color: var(--secondary-color);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

#confirm-changepass,
#confirm-changeemail {
    background-color: var(--primary-color);
    color: var(--secondary-color);
    border: none;
    cursor: pointer;
}

#confirm-changepass:hover,
#confirm-changeemail:hover {
    background-color: var(--secondary-color);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}


#confirm-changeusername {
    background-color: var(--primary-color);
    color: var(--secondary-color);
}

#confirm-changeusername:hover {
    background-color: var(--secondary-color);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}


/* CONFIRM USERNAME + EMAIL CHANGE */
#confirm-changeusernameemail {
    background-color: var(--primary-color);
    color: var(--secondary-color);
}

#confirm-changeusernameemail:hover {
    background-color: var(--secondary-color);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.profile-info input.editing {
    border: 1px solid var(--primary-color);
}


/* NOTIFICATION SETTINGS */

.notifications-container h1 {
    color: var(--primary-color);
}

.notification-item {
    background-color: var(--background-color);
    border-left: 5px solid var(--primary-color);
}

.notification-item h3 {
    color: var(--primary-color);
}

/* TABLES */

th, .ordered-product {
    background-color: var(--primary-color);
    color: var(--secondary-color);
}

.confirm-modal h3 {
    color: var(--primary-color);
}

.confirm-action {
    background-color: var(--primary-color);
    color: var(--secondary-color);
}

.confirm-action:hover {
    background-color: var(--secondary-color);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}


/* PRODUCT PAGE*/


.product-info h1 {
    color: var(--primary-color);
}


.product-info label {
    color: var(--primary-color);
}

.product-info select {
    background-color: var(--secondary-color);
}

.product-info input {
    background-color: var(--secondary-color);
}

.product-info input:focus {
    border-color: var(--primary-color);
}


.number-input-container .button {
    background-color: var(--primary-color);
    color: var(--secondary-color);
}

.number-input {
    border: 5px solid var(--primary-color);
}

.add-product-button {
    background-color: var(--primary-color);
    color: var(--secondary-color);
}

.add-product-button:hover {
    background-color: var(--secondary-color);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}


.carousel-btn{
    background: var(--primary-color);
    color: var(--secondary-color);
}

.carousel-btn:hover{
    background-color: var(--secondary-color);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}


.prev, .next {
  background-color: transparent;
  color: var(--primary-color);
  border-color: var(--primary-color);
}

.prev:hover, .next:hover {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}