<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * [SISTEM KUA] Normalisasi nomor HP ke format WhatsApp.
 */
class PhoneNumberTest extends TestCase
{
    public static function nomorProvider(): array
    {
        return [
            'awalan nol' => ['081234567890', '6281234567890'],
            'awalan +62' => ['+6281234567890', '6281234567890'],
            'awalan 62' => ['6281234567890', '6281234567890'],
            'tanpa awalan' => ['81234567890', '6281234567890'],
            'pakai spasi' => ['0812 3456 7890', '6281234567890'],
            'pakai strip' => ['0812-3456-7890', '6281234567890'],
            'pakai kurung' => ['(0812) 3456 7890', '6281234567890'],
        ];
    }

    #[DataProvider('nomorProvider')]
    public function test_normalize_various_formats(string $masukan, string $harapan): void
    {
        $this->assertSame($harapan, PhoneNumber::normalize($masukan));
    }

    public function test_normalize_rejects_invalid(): void
    {
        $this->assertNull(PhoneNumber::normalize(null));
        $this->assertNull(PhoneNumber::normalize(''));
        $this->assertNull(PhoneNumber::normalize('bukan nomor'));
        $this->assertNull(PhoneNumber::normalize('0812'));        // terlalu pendek
    }

    public function test_format_is_readable(): void
    {
        $this->assertSame('+62 812-3456-7890', PhoneNumber::format('081234567890'));
        $this->assertSame('-', PhoneNumber::format('bukan nomor'));
    }

    public function test_wa_me_link_encodes_message(): void
    {
        $link = PhoneNumber::waMeLink('081234567890', 'Halo KUA, saya mau tanya.');

        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $link);
        $this->assertStringContainsString('Halo%20KUA', $link);
        $this->assertNull(PhoneNumber::waMeLink(null));
    }
}
