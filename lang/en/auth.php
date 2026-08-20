<?php

// Overrides Laravel's own built-in English auth.* strings (there's no lang/
// override for them otherwise, and app locale is 'en' by default per
// config/app.php) so a wrong-credentials or rate-limit message reaching the
// user is in Indonesian like the rest of the app, instead of leaking the
// framework's stock English text.
return [

    'failed' => 'NIP atau kata sandi yang Anda masukkan salah.',
    'password' => 'Kata sandi yang Anda masukkan salah.',
    'throttle' => 'Terlalu banyak percobaan masuk. Coba lagi dalam :seconds detik.',

];
