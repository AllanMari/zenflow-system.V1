<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\RoomCategory;
use App\Models\PackageChoiceGroup;
use App\Models\PackageChoiceOption;

class MenuPackagesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Resolve Categories
        $pkgCat = ServiceCategory::firstOrCreate(['name' => 'Packages'], ['color' => '#8b5cf6', 'is_active' => true]);
        $coupleCat = ServiceCategory::firstOrCreate(['name' => 'Couple Spa'], ['color' => '#f43f5e', 'is_active' => true]);
        $vipCat = ServiceCategory::where('id', 9)->orWhere('name', 'VIP Suite Package')->orWhere('name', 'VIP Packages')->first();
        if ($vipCat) {
            $vipCat->update(['name' => 'VIP Packages', 'color' => '#eab308', 'is_active' => true]);
        } else {
            $vipCat = ServiceCategory::firstOrCreate(['name' => 'VIP Packages'], ['color' => '#eab308', 'is_active' => true]);
        }

        // 2. Resolve Room Categories
        $standardRoomCat = RoomCategory::where('code', 'STANDARD')->first();
        $coupleRoomCat = RoomCategory::where('code', 'COUPLE')->first();
        $vipRoomCat = RoomCategory::where('code', 'VIP')->first();

        // 3. Resolve Individual Component Services
        $bodyScrub = Service::where('name', 'Body Scrub')->firstOrFail();
        $footSpa = Service::where('name', 'Foot Spa')->firstOrFail();
        $facialCleansing = Service::where('name', 'Facial Cleansing')->firstOrFail();
        $refreshingFoot = Service::where('name', 'Refreshing Footmassage')->firstOrFail();
        $restfulHead = Service::where('name', 'Restful Head Massage')->firstOrFail();
        $alexandriaMassage = Service::where('name', 'Alexandria Massage')->firstOrFail();
        $swedishMassage = Service::where('name', 'Swedish Massage')->where('price', 530)->firstOrFail();
        $ventosaMassage = Service::where('name', 'Ventosa Massage')->firstOrFail();
        $thermalMassage = Service::where('name', 'Thermal Massage')->firstOrFail();

        // 4. Create Standard Packages 1–6
        $standardPackages = [
            [
                'name' => 'Package 1',
                'duration_minutes' => 90,
                'price' => 860.00,
                'description' => 'Body Scrub + Facial Cleansing',
                'landing_description' => 'Body Scrub + Facial Cleansing',
                'included_services' => [$bodyScrub->id, $facialCleansing->id],
                'room_category_id' => $standardRoomCat->id,
            ],
            [
                'name' => 'Package 2',
                'duration_minutes' => 90,
                'price' => 940.00,
                'description' => "Body Scrub + Alexandria's Massage",
                'landing_description' => "Body Scrub + Alexandria's Massage",
                'included_services' => [$bodyScrub->id, $alexandriaMassage->id],
                'room_category_id' => $standardRoomCat->id,
            ],
            [
                'name' => 'Package 3',
                'duration_minutes' => 90,
                'price' => 750.00,
                'description' => 'Foot Scrub + Foot Massage',
                'landing_description' => 'Foot Scrub + Refreshing Footmassage (30 mins)',
                'included_services' => [$footSpa->id, $refreshingFoot->id],
                'room_category_id' => $standardRoomCat->id,
            ],
            [
                'name' => 'Package 4',
                'duration_minutes' => 90,
                'price' => 680.00,
                'description' => 'Foot Scrub + Head Massage',
                'landing_description' => 'Foot Scrub + Restful Head Massage (30 mins)',
                'included_services' => [$footSpa->id, $restfulHead->id],
                'room_category_id' => $standardRoomCat->id,
            ],
            [
                'name' => 'Package 5',
                'duration_minutes' => 60,
                'price' => 620.00,
                'description' => 'Foot Massage + Facial Cleansing',
                'landing_description' => 'Refreshing Footmassage (30 mins) + Facial Cleansing',
                'included_services' => [$refreshingFoot->id, $facialCleansing->id],
                'room_category_id' => $standardRoomCat->id,
            ],
            [
                'name' => 'Package 6',
                'duration_minutes' => 90,
                'price' => 900.00,
                'description' => 'Foot Scrub W/ Foot Massage + Head Massage',
                'landing_description' => 'Foot Scrub + Refreshing Footmassage (30 mins) + Restful Head Massage (30 mins)',
                'included_services' => [$footSpa->id, $refreshingFoot->id, $restfulHead->id],
                'room_category_id' => $standardRoomCat->id,
            ],
        ];

        foreach ($standardPackages as $pkgData) {
            Service::updateOrCreate(
                ['name' => $pkgData['name'], 'is_package' => true],
                [
                    'category_id' => $pkgCat->id,
                    'duration_minutes' => $pkgData['duration_minutes'],
                    'price' => $pkgData['price'],
                    'description' => $pkgData['description'],
                    'landing_description' => $pkgData['landing_description'],
                    'included_services' => $pkgData['included_services'],
                    'participants_required' => 1,
                    'therapists_required' => 1,
                    'requires_room' => true,
                    'room_category_id' => $pkgData['room_category_id'],
                    'is_active' => true,
                    'show_on_landing' => true,
                ]
            );
        }

        // 5. Create Couple Spa Packages
        $couple1 = Service::updateOrCreate(
            ['name' => 'Couple 1', 'is_package' => true],
            [
                'category_id' => $coupleCat->id,
                'duration_minutes' => 120,
                'price' => 2299.00,
                'description' => "Foot Scrub, Facial Cleansing, Alexandria's or Swedish Massage",
                'landing_description' => "Foot Scrub, Facial Cleansing, plus your choice of Alexandria's or Swedish Massage",
                'included_services' => [$footSpa->id, $facialCleansing->id],
                'participants_required' => 2,
                'therapists_required' => 2,
                'requires_room' => true,
                'room_category_id' => $coupleRoomCat->id,
                'is_active' => true,
                'show_on_landing' => true,
            ]
        );
        $group1 = PackageChoiceGroup::firstOrCreate(
            ['service_id' => $couple1->id, 'label' => 'Choose Your Massage'],
            ['is_required' => true, 'max_selections' => 1]
        );
        PackageChoiceOption::firstOrCreate(['choice_group_id' => $group1->id, 'service_id' => $alexandriaMassage->id], ['sort_order' => 1]);
        PackageChoiceOption::firstOrCreate(['choice_group_id' => $group1->id, 'service_id' => $swedishMassage->id], ['sort_order' => 2]);

        $couple2 = Service::updateOrCreate(
            ['name' => 'Couple 2', 'is_package' => true],
            [
                'category_id' => $coupleCat->id,
                'duration_minutes' => 90,
                'price' => 1449.00,
                'description' => 'Ventosa or Thermal Massage, 30 Mins Foot Massage',
                'landing_description' => 'Your choice of Ventosa or Thermal Massage, plus 30 Mins Refreshing Footmassage',
                'included_services' => [$refreshingFoot->id],
                'participants_required' => 2,
                'therapists_required' => 2,
                'requires_room' => true,
                'room_category_id' => $coupleRoomCat->id,
                'is_active' => true,
                'show_on_landing' => true,
            ]
        );
        $group2 = PackageChoiceGroup::firstOrCreate(
            ['service_id' => $couple2->id, 'label' => 'Choose Your Massage'],
            ['is_required' => true, 'max_selections' => 1]
        );
        PackageChoiceOption::firstOrCreate(['choice_group_id' => $group2->id, 'service_id' => $ventosaMassage->id], ['sort_order' => 1]);
        PackageChoiceOption::firstOrCreate(['choice_group_id' => $group2->id, 'service_id' => $thermalMassage->id], ['sort_order' => 2]);

        $couple3 = Service::updateOrCreate(
            ['name' => 'Couple 3', 'is_package' => true],
            [
                'category_id' => $coupleCat->id,
                'duration_minutes' => 90,
                'price' => 1799.00,
                'description' => "Body Scrub, Alexandria's or Swedish Massage",
                'landing_description' => "Body Scrub, plus your choice of Alexandria's or Swedish Massage",
                'included_services' => [$bodyScrub->id],
                'participants_required' => 2,
                'therapists_required' => 2,
                'requires_room' => true,
                'room_category_id' => $coupleRoomCat->id,
                'is_active' => true,
                'show_on_landing' => true,
            ]
        );
        $group3 = PackageChoiceGroup::firstOrCreate(
            ['service_id' => $couple3->id, 'label' => 'Choose Your Massage'],
            ['is_required' => true, 'max_selections' => 1]
        );
        PackageChoiceOption::firstOrCreate(['choice_group_id' => $group3->id, 'service_id' => $alexandriaMassage->id], ['sort_order' => 1]);
        PackageChoiceOption::firstOrCreate(['choice_group_id' => $group3->id, 'service_id' => $swedishMassage->id], ['sort_order' => 2]);

        // 6. Create VIP Lounge Packages (For 2 at VIP Lounge)
        // 6.1 Royal Vip Package (updates/preserves VIP Suite Package id 34)
        $royalPkg = Service::where('id', 34)->orWhere('name', 'Royal Vip Package')->orWhere('name', 'VIP Suite Package')->first();
        $royalData = [
            'category_id' => $vipCat->id,
            'name' => 'Royal Vip Package',
            'duration_minutes' => 150,
            'price' => 3899.00,
            'description' => "Room Exclusive for TWO at VIP Lounge!\nUltimate Relaxation for Two\n\nInclusions:\n• Sauna\n• Jacuzzi\n• Alexandria Massage\n• Grazing Platter & Wine\n• Hot & Cold Shower",
            'landing_description' => "Ultimate Relaxation for Two: Sauna, Jacuzzi, Alexandria Massage, Grazing Platter & Wine, Hot & Cold Shower.",
            'included_services' => [$alexandriaMassage->id],
            'participants_required' => 2,
            'therapists_required' => 2,
            'requires_room' => true,
            'room_category_id' => $vipRoomCat->id,
            'is_active' => true,
            'show_on_landing' => true,
            'is_package' => true,
        ];

        if ($royalPkg) {
            $royalPkg->update($royalData);
        } else {
            Service::create(array_merge(['id' => 34], $royalData));
        }

        // 6.2 Deluxe Vip Package
        Service::updateOrCreate(
            ['name' => 'Deluxe Vip Package', 'is_package' => true],
            [
                'category_id' => $vipCat->id,
                'duration_minutes' => 120,
                'price' => 3399.00,
                'description' => "Room Exclusive for TWO at VIP Lounge!\nRenew Refresh Rejuvenate\n\nInclusions:\n• Sauna\n• Body Scrub\n• Alexandria Massage\n• Hot & Cold Shower",
                'landing_description' => "Renew Refresh Rejuvenate: Sauna, Body Scrub, Alexandria Massage, Hot & Cold Shower.",
                'included_services' => [$bodyScrub->id, $alexandriaMassage->id],
                'participants_required' => 2,
                'therapists_required' => 2,
                'requires_room' => true,
                'room_category_id' => $vipRoomCat->id,
                'is_active' => true,
                'show_on_landing' => true,
            ]
        );

        // 6.3 Signature Vip Package
        Service::updateOrCreate(
            ['name' => 'Signature Vip Package', 'is_package' => true],
            [
                'category_id' => $vipCat->id,
                'duration_minutes' => 120,
                'price' => 3199.00,
                'description' => "Room Exclusive for TWO at VIP Lounge!\nRadiant Skin Total Bliss\n\nInclusions:\n• Sauna\n• Facial (Facial Cleansing)\n• Alexandria Massage\n• Hot & Cold Shower",
                'landing_description' => "Radiant Skin Total Bliss: Sauna, Facial Cleansing, Alexandria Massage, Hot & Cold Shower.",
                'included_services' => [$facialCleansing->id, $alexandriaMassage->id],
                'participants_required' => 2,
                'therapists_required' => 2,
                'requires_room' => true,
                'room_category_id' => $vipRoomCat->id,
                'is_active' => true,
                'show_on_landing' => true,
            ]
        );

        // 6.4 Refreshing Vip Package
        Service::updateOrCreate(
            ['name' => 'Refreshing Vip Package', 'is_package' => true],
            [
                'category_id' => $vipCat->id,
                'duration_minutes' => 90,
                'price' => 1999.00,
                'description' => "Room Exclusive for TWO at VIP Lounge!\nQuick Escape Feel the Difference\n\nInclusions:\n• Sauna\n• Alexandria Massage\n• Hot & Cold Shower",
                'landing_description' => "Quick Escape Feel the Difference: Sauna, Alexandria Massage, Hot & Cold Shower.",
                'included_services' => [$alexandriaMassage->id],
                'participants_required' => 2,
                'therapists_required' => 2,
                'requires_room' => true,
                'room_category_id' => $vipRoomCat->id,
                'is_active' => true,
                'show_on_landing' => true,
            ]
        );
    }
}
