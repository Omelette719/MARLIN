<?php

namespace Tests\Feature\Admin;

use App\Enums\Urgensi;
use App\Livewire\Admin\Spk\Create as SpkCreateComponent;
use App\Livewire\Admin\Spk\Edit as SpkEditComponent;
use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use App\Support\PenyesuaianDeadlineSpk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PenyesuaianDeadlineSpkTest extends TestCase
{
    use RefreshDatabase;

    private static int $nomorUrut = 0;

    private function makeSpk(User $admin, array $attrs = []): Spk
    {
        return Spk::create(array_merge([
            'nomor_surat' => 'SR-2026/BJM/3'.str_pad((string) ++self::$nomorUrut, 4, '0', STR_PAD_LEFT),
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'jalan' => 'Veteran',
            'rt' => '5',
            'kelurahan' => 'Antasan Besar',
            'deadline' => now()->addDays(4)->toDateString(),
            'deadline_asli' => now()->addDays(4)->toDateString(),
            'urgensi' => 'sedang',
            'prioritas' => false,
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ], $attrs));
    }

    private function createPrioritySpk(User $admin, int $hariDeadline): Spk
    {
        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan '.random_int(1000, 9999)]);

        Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', 'pasang_baru')
            ->set('jalan', 'Lambung Mangkurat')
            ->set('rt', '5')
            ->set('kelurahan', 'Kertak Baru')
            ->set('deadline', now()->addDays($hariDeadline)->toDateString())
            ->set('prioritas', true)
            ->set('asal_permintaan', 'internal')
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambu->id)
            ->set('rambuItems.0.lokasi', 'Perempatan dekat masjid')
            ->set('rambuItems.0.koordinat', '-3.3194,114.5908')
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasNoErrors();

        return Spk::latest('id')->first();
    }

    public function test_deadline_asli_is_set_on_creation(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        Livewire::test(SpkCreateComponent::class)
            ->set('jalan', 'Lambung Mangkurat')
            ->set('rt', '5')
            ->set('kelurahan', 'Kertak Baru')
            ->set('deadline', now()->addDays(6)->toDateString())
            ->set('asal_permintaan', 'internal')
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambu->id)
            ->set('rambuItems.0.lokasi', 'Perempatan dekat masjid')
            ->set('rambuItems.0.koordinat', '-3.3194,114.5908')
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasNoErrors();

        $spk = Spk::latest('id')->first();

        $this->assertSame(now()->addDays(6)->toDateString(), $spk->deadline_asli->toDateString());
        $this->assertSame(now()->addDays(6)->toDateString(), $spk->deadline->toDateString());
    }

    public function test_creating_priority_spk_pushes_other_active_spk_deadline_by_half_floored(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $lain = $this->makeSpk($admin, ['deadline' => now()->addDays(4), 'deadline_asli' => now()->addDays(4)]);

        $this->createPrioritySpk($admin, 10); // floor(10/2) = 5

        $this->assertSame(now()->addDays(4)->addDays(5)->toDateString(), $lain->fresh()->deadline->toDateString());
    }

    #[DataProvider('floorPembagianProvider')]
    public function test_odd_day_counts_are_floored_correctly(int $hariPrioritas, int $tambahanDiharapkan): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $lain = $this->makeSpk($admin, ['deadline' => now()->addDays(1), 'deadline_asli' => now()->addDays(1)]);

        $this->createPrioritySpk($admin, $hariPrioritas);

        $this->assertSame(
            now()->addDays(1)->addDays($tambahanDiharapkan)->toDateString(),
            $lain->fresh()->deadline->toDateString()
        );
    }

    public static function floorPembagianProvider(): array
    {
        return [
            '2 hari -> +1' => [2, 1],
            '10 hari -> +5' => [10, 5],
            '11 hari -> +5' => [11, 5],
            '7 hari -> +3' => [7, 3],
            '5 hari -> +2' => [5, 2],
            '3 hari -> +1' => [3, 1],
        ];
    }

    public function test_other_priority_spk_is_not_pushed(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $prioritasLain = $this->makeSpk($admin, [
            'deadline' => now()->addDays(3),
            'deadline_asli' => now()->addDays(3),
            'prioritas' => true,
            'urgensi' => 'tinggi',
        ]);

        $this->createPrioritySpk($admin, 10);

        $this->assertSame(now()->addDays(3)->toDateString(), $prioritasLain->fresh()->deadline->toDateString());
    }

    public function test_selesai_and_dibatalkan_spk_are_not_pushed(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $selesai = $this->makeSpk($admin, ['status' => 'selesai', 'deadline' => now()->addDays(2), 'deadline_asli' => now()->addDays(2)]);
        $dibatalkan = $this->makeSpk($admin, ['status' => 'dibatalkan', 'deadline' => now()->addDays(2), 'deadline_asli' => now()->addDays(2)]);

        $this->createPrioritySpk($admin, 10);

        $this->assertSame(now()->addDays(2)->toDateString(), $selesai->fresh()->deadline->toDateString());
        $this->assertSame(now()->addDays(2)->toDateString(), $dibatalkan->fresh()->deadline->toDateString());
    }

    public function test_multiple_priority_spk_take_the_max_push_not_the_sum(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $lain = $this->makeSpk($admin, ['deadline' => now()->addDays(1), 'deadline_asli' => now()->addDays(1)]);

        $this->createPrioritySpk($admin, 2); // +1
        $setelahPertama = $lain->fresh()->deadline->toDateString();
        $this->assertSame(now()->addDays(1)->addDays(1)->toDateString(), $setelahPertama);

        $this->createPrioritySpk($admin, 4); // floor(4/2) = 2, still less than the +1 already pending? no, 2 > 1
        $this->assertSame(now()->addDays(1)->addDays(2)->toDateString(), $lain->fresh()->deadline->toDateString());

        // A smaller priority SPK afterwards must not roll the deadline back.
        $this->createPrioritySpk($admin, 2); // +1, smaller than the +2 already applied
        $this->assertSame(now()->addDays(1)->addDays(2)->toDateString(), $lain->fresh()->deadline->toDateString());
    }

    public function test_urgensi_is_recomputed_when_deadline_is_pushed(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        // Starts far enough out to be Rendah, gets pushed further by a big priority SPK.
        $lain = $this->makeSpk($admin, [
            'deadline' => now()->addDays(20),
            'deadline_asli' => now()->addDays(20),
            'urgensi' => 'rendah',
        ]);

        $this->createPrioritySpk($admin, 10); // +5

        $this->assertSame(Urgensi::Rendah, $lain->fresh()->urgensi);
        $this->assertSame(now()->addDays(20)->addDays(5)->toDateString(), $lain->fresh()->deadline->toDateString());
    }

    public function test_audit_log_is_created_for_pushed_spk(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $lain = $this->makeSpk($admin, ['deadline' => now()->addDays(4), 'deadline_asli' => now()->addDays(4)]);

        $this->createPrioritySpk($admin, 10);

        $this->assertSame(1, $lain->auditLogs()->where('aksi', 'deadline_disesuaikan')->count());
    }

    public function test_editing_deadline_resets_deadline_asli(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $spk = $this->makeSpk($admin, ['deadline' => now()->addDays(4), 'deadline_asli' => now()->addDays(4)]);
        RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => Rambu::create([
                'jenis_rambu_id' => $jenisRambu->id,
                'wilayah' => 'Banjarmasin Tengah',
                'lokasi' => 'A',
                'koordinat' => '-3.30,114.59',
            ])->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
        ]);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->set('deadline', now()->addDays(15)->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $spk->refresh();

        $this->assertSame(now()->addDays(15)->toDateString(), $spk->deadline->toDateString());
        $this->assertSame(now()->addDays(15)->toDateString(), $spk->deadline_asli->toDateString());
    }

    public function test_penyesuaian_does_nothing_when_spk_is_not_prioritas(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $lain = $this->makeSpk($admin, ['deadline' => now()->addDays(4), 'deadline_asli' => now()->addDays(4)]);
        $bukanPrioritas = $this->makeSpk($admin, ['deadline' => now()->addDays(10), 'deadline_asli' => now()->addDays(10), 'prioritas' => false]);

        PenyesuaianDeadlineSpk::terapkan($bukanPrioritas);

        $this->assertSame(now()->addDays(4)->toDateString(), $lain->fresh()->deadline->toDateString());
    }
}
