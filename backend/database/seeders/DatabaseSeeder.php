<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Contact;
use App\Models\Facility;
use App\Models\Favorite;
use App\Models\Kost;
use App\Models\Room;
use App\Models\RoomPhoto;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owners = collect([
            User::create([
                'name' => 'Owner 1',
                'email' => 'owner1@kostku.test',
                'password' => 'password123',
                'phone' => '081200000001',
                'role' => 'owner',
            ]),
            User::create([
                'name' => 'Owner 2',
                'email' => 'owner2@kostku.test',
                'password' => 'password123',
                'phone' => '081200000002',
                'role' => 'owner',
            ]),
            User::create([
                'name' => 'Owner 3',
                'email' => 'owner3@kostku.test',
                'password' => 'password123',
                'phone' => '081200000003',
                'role' => 'owner',
            ]),
        ]);

        $seekers = collect([
            User::create([
                'name' => 'Seeker 1',
                'email' => 'seeker1@kostku.test',
                'password' => 'password123',
                'phone' => '081300000001',
                'role' => 'seeker',
            ]),
            User::create([
                'name' => 'Seeker 2',
                'email' => 'seeker2@kostku.test',
                'password' => 'password123',
                'phone' => '081300000002',
                'role' => 'seeker',
            ]),
        ]);

        $facilityNames = [
            'WiFi',
            'AC',
            'Kamar Mandi Dalam',
            'Kasur',
            'Lemari',
            'Meja Belajar',
            'Parkir Motor',
            'Dapur',
            'Laundry',
            'CCTV',
        ];

        $facilities = collect($facilityNames)->map(function ($name) {
            return Facility::create([
                'name' => $name,
                'icon' => null,
            ]);
        });

        $kostSeeds = [
            [
                'name' => 'Kost Harmoni Kemang',
                'description' => 'Kost nyaman dekat pusat kuliner dan transportasi.',
                'address' => 'Jl. Kemang Raya No. 10',
                'city' => 'Jakarta Selatan',
                'province' => 'DKI Jakarta',
                'latitude' => -6.260718,
                'longitude' => 106.813651,
                'type' => 'campur',
            ],
            [
                'name' => 'Kost Cendana Dago',
                'description' => 'Dekat kampus, lingkungan tenang.',
                'address' => 'Jl. Ir. H. Juanda No. 50',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'latitude' => -6.893600,
                'longitude' => 107.610400,
                'type' => 'putri',
            ],
            [
                'name' => 'Kost Sakinah Ketintang',
                'description' => 'Strategis dekat kampus dan pusat belanja.',
                'address' => 'Jl. Ketintang Baru No. 8',
                'city' => 'Surabaya',
                'province' => 'Jawa Timur',
                'latitude' => -7.305000,
                'longitude' => 112.728000,
                'type' => 'putra',
            ],
            [
                'name' => 'Kost Prawirotaman',
                'description' => 'Area wisata, banyak pilihan kuliner.',
                'address' => 'Jl. Prawirotaman No. 12',
                'city' => 'Yogyakarta',
                'province' => 'DI Yogyakarta',
                'latitude' => -7.813000,
                'longitude' => 110.370000,
                'type' => 'campur',
            ],
            [
                'name' => 'Kost Tembalang Asri',
                'description' => 'Dekat UNDIP, cocok untuk mahasiswa.',
                'address' => 'Jl. Prof. Soedarto No. 5',
                'city' => 'Semarang',
                'province' => 'Jawa Tengah',
                'latitude' => -7.053000,
                'longitude' => 110.440000,
                'type' => 'putri',
            ],
            [
                'name' => 'Kost Soekarno Hatta',
                'description' => 'Akses mudah ke pusat kota dan kampus.',
                'address' => 'Jl. Soekarno Hatta No. 90',
                'city' => 'Malang',
                'province' => 'Jawa Timur',
                'latitude' => -7.955000,
                'longitude' => 112.616000,
                'type' => 'putra',
            ],
            [
                'name' => 'Kost Setiabudi Medan',
                'description' => 'Kost bersih, fasilitas lengkap.',
                'address' => 'Jl. Setiabudi No. 25',
                'city' => 'Medan',
                'province' => 'Sumatera Utara',
                'latitude' => 3.561000,
                'longitude' => 98.656000,
                'type' => 'campur',
            ],
            [
                'name' => 'Kost Renon Bali',
                'description' => 'Dekat pusat perkantoran, parkir luas.',
                'address' => 'Jl. Raya Puputan No. 77',
                'city' => 'Denpasar',
                'province' => 'Bali',
                'latitude' => -8.670000,
                'longitude' => 115.235000,
                'type' => 'campur',
            ],
        ];

        $kosts = collect($kostSeeds)->map(function ($seed, $i) use ($owners, $facilities) {
            $owner = $owners[$i % $owners->count()];

            $kost = Kost::create([
                'owner_id' => $owner->id,
                'name' => $seed['name'],
                'description' => $seed['description'],
                'address' => $seed['address'],
                'city' => $seed['city'],
                'province' => $seed['province'],
                'latitude' => $seed['latitude'],
                'longitude' => $seed['longitude'],
                'type' => $seed['type'],
            ]);

            $kost->facilities()->sync($facilities->random(rand(3, 7))->pluck('id')->all());

            return $kost;
        });

        $rooms = collect();
        $targetRooms = 20;
        $roomIndex = 1;

        while ($rooms->count() < $targetRooms) {
            foreach ($kosts as $kost) {
                if ($rooms->count() >= $targetRooms) {
                    break;
                }

                $roomsPerKost = rand(2, 3);
                for ($j = 0; $j < $roomsPerKost; $j++) {
                    if ($rooms->count() >= $targetRooms) {
                        break;
                    }

                    $price = rand(500000, 3000000);

                    $room = Room::create([
                        'kost_id' => $kost->id,
                        'name' => 'Kamar ' . $roomIndex,
                        'price' => $price,
                        'is_available' => (bool) rand(0, 1),
                        'size' => Arr::random([9, 12, 16, null]),
                        'capacity' => Arr::random([1, 1, 1, 2]),
                        'description' => 'Kamar nyaman, ventilasi baik, cocok untuk mahasiswa/pekerja.',
                    ]);

                    $room->facilities()->sync($facilities->random(rand(2, 5))->pluck('id')->all());

                    $rooms->push($room);
                    $roomIndex++;
                }
            }
        }

        $photosTarget = 40;
        $photoCount = 0;
        foreach ($rooms as $room) {
            $photosPerRoom = 2;
            for ($p = 0; $p < $photosPerRoom; $p++) {
                if ($photoCount >= $photosTarget) {
                    break 2;
                }

                $photoCount++;
                RoomPhoto::create([
                    'room_id' => $room->id,
                    'photo_url' => '/storage/room_photos/dummy_' . $room->id . '_' . $photoCount . '.jpg',
                    'is_primary' => $p === 0,
                ]);
            }
        }

        foreach ($seekers as $seeker) {
            $favoriteKosts = $kosts->random(rand(2, 4));
            foreach ($favoriteKosts as $kost) {
                Favorite::firstOrCreate([
                    'user_id' => $seeker->id,
                    'kost_id' => $kost->id,
                ]);
            }
        }

        // Optional contacts dummy
        foreach ($seekers as $seeker) {
            $kost = $kosts->random();
            Contact::create([
                'seeker_id' => $seeker->id,
                'owner_id' => $kost->owner_id,
                'kost_id' => $kost->id,
                'message' => 'Halo, saya ingin tanya ketersediaan kamar dan biaya tambahan.',
                'status' => Arr::random(['pending', 'responded', 'closed']),
            ]);
        }
    }
}
