<?php

namespace App\Rules;

use App\Models\Rambu;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Coordinates are stored as a plain "lat,lng" string (see Rambu::parseKoordinat())
 * and drive map pin placement — an unparsable value doesn't error anywhere, it just
 * makes the rambu silently vanish from the peta. Catch that at input time instead.
 */
class Koordinat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Rambu::parseKoordinat($value)) {
            $fail('Format koordinat harus "lintang,bujur" dalam angka desimal, contoh: -3.3194,114.5908.');

            return;
        }

        [$lat, $lng] = Rambu::parseKoordinat($value);

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            $fail('Nilai koordinat di luar jangkauan yang valid (lintang -90 s/d 90, bujur -180 s/d 180).');
        }
    }
}
