<?php
// ---- Database ----
const DB_HOST = 'localhost';
const DB_NAME = 'rp_ngoma';
const DB_USER = 'root';
const DB_PASS = '';          // XAMPP default is empty

// ---- SMS (Africa's Talking). Leave API key empty to simulate: messages are only saved in sms_log. ----
const SMS_USERNAME = 'sandbox';   // your Africa's Talking username
const SMS_API_KEY  = '';          // paste API key to send real SMS
const SMS_SENDER   = '';          // optional approved sender ID
const SMS_URL      = 'https://api.sandbox.africastalking.com/version1/messaging'; // live: https://api.africastalking.com/version1/messaging

// ---- Set true only while developing; keep false on the live site ----
const DEBUG = false;
