<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration Schema Version
    |--------------------------------------------------------------------------
    */
    'schema_version' => 1,

    /*
    |--------------------------------------------------------------------------
    | Unit Conversion Factors (to PDF Points: 1 in = 72 pt)
    |--------------------------------------------------------------------------
    */
    'points_per_inch' => 72.0,
    'mm_per_inch' => 25.4,

    /*
    |--------------------------------------------------------------------------
    | Dimension & Setting Bounds
    |--------------------------------------------------------------------------
    */
    'bounds' => [
        'width_mm' => ['min' => 40.0, 'max' => 600.0],
        'height_mm' => ['min' => 40.0, 'max' => 2000.0],
        'margin_mm' => ['min' => 0.0, 'max' => 60.0],
        'scale_percent' => ['min' => 50, 'max' => 150],
        'font_size_px' => ['min' => 6, 'max' => 18],
    ],

    /*
    |--------------------------------------------------------------------------
    | Standard Paper Sizes (Portrait base dimensions in millimeters)
    |--------------------------------------------------------------------------
    */
    'papers' => [
        'a4' => [
            'label' => 'A4',
            'width_mm' => 210.0,
            'height_mm' => 297.0,
            'supports_orientation' => true,
        ],
        'a3' => [
            'label' => 'A3',
            'width_mm' => 297.0,
            'height_mm' => 420.0,
            'supports_orientation' => true,
        ],
        'a5' => [
            'label' => 'A5',
            'width_mm' => 148.0,
            'height_mm' => 210.0,
            'supports_orientation' => true,
        ],
        'letter' => [
            'label' => 'Letter',
            'width_mm' => 215.9,
            'height_mm' => 279.4,
            'supports_orientation' => true,
        ],
        'legal' => [
            'label' => 'Legal',
            'width_mm' => 215.9,
            'height_mm' => 355.6,
            'supports_orientation' => true,
        ],
        'compact_100x150' => [
            'label' => 'Compact (100 × 150 mm)',
            'width_mm' => 100.0,
            'height_mm' => 150.0,
            'supports_orientation' => true,
        ],
        'roll_80' => [
            'label' => 'Thermal Roll 80 mm',
            'width_mm' => 80.0,
            'height_mm' => 297.0,
            'supports_orientation' => false,
        ],
        'roll_58' => [
            'label' => 'Thermal Roll 58 mm',
            'width_mm' => 58.0,
            'height_mm' => 297.0,
            'supports_orientation' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Margins (millimeters)
    |--------------------------------------------------------------------------
    | Note: 35px at 96 DPI ≈ 9.26mm (matches existing report templates).
    */
    'default_margins_mm' => [
        'top' => 9.26,
        'right' => 9.26,
        'bottom' => 9.26,
        'left' => 9.26,
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Presets
    |--------------------------------------------------------------------------
    */
    'presets' => [
        'compact_100x150' => [
            'label' => 'Compact Invoice (100 × 150 mm)',
            'paper' => 'compact_100x150',
            'orientation' => 'portrait',
            'margins_mm' => ['top' => 5.0, 'right' => 5.0, 'bottom' => 5.0, 'left' => 5.0],
            'content_frame' => [
                'enabled' => false,
                'width_mm' => null,
                'height_mm' => null,
                'alignment' => 'top-center',
                'offset_x_mm' => 0.0,
                'offset_y_mm' => 0.0,
            ],
            'scale_percent' => 100,
            'base_font_size_px' => 8,
            'fit_to_page' => true,
            'repeat_table_headers' => false,
            'show_page_numbers' => false,
            'capabilities' => [
                'orientation' => true,
                'content_frame' => false,
                'fit_to_page' => true,
                'custom_size' => false,
            ],
        ],
        'receipt_on_a4' => [
            'label' => 'Receipt on A4',
            'paper' => 'a4',
            'orientation' => 'portrait',
            'margins_mm' => ['top' => 12.0, 'right' => 12.0, 'bottom' => 12.0, 'left' => 12.0],
            'content_frame' => [
                'enabled' => true,
                'width_mm' => 90.0,
                'height_mm' => null,
                'alignment' => 'top-center',
                'offset_x_mm' => 0.0,
                'offset_y_mm' => 0.0,
            ],
            'scale_percent' => 100,
            'base_font_size_px' => 8,
            'fit_to_page' => false,
            'repeat_table_headers' => false,
            'show_page_numbers' => false,
            'capabilities' => [
                'orientation' => true,
                'content_frame' => true,
                'fit_to_page' => false,
                'custom_size' => false,
            ],
        ],
        'thermal_80' => [
            'label' => 'Thermal Receipt 80 mm',
            'paper' => 'roll_80',
            'orientation' => 'portrait',
            'margins_mm' => ['top' => 4.0, 'right' => 4.0, 'bottom' => 4.0, 'left' => 4.0],
            'content_frame' => [
                'enabled' => false,
                'width_mm' => null,
                'height_mm' => null,
                'alignment' => 'top-center',
                'offset_x_mm' => 0.0,
                'offset_y_mm' => 0.0,
            ],
            'scale_percent' => 100,
            'base_font_size_px' => 8,
            'fit_to_page' => false,
            'repeat_table_headers' => false,
            'show_page_numbers' => false,
            'capabilities' => [
                'orientation' => false,
                'content_frame' => false,
                'fit_to_page' => false,
                'custom_size' => false,
            ],
        ],
        'thermal_58' => [
            'label' => 'Thermal Receipt 58 mm',
            'paper' => 'roll_58',
            'orientation' => 'portrait',
            'margins_mm' => ['top' => 3.0, 'right' => 3.0, 'bottom' => 3.0, 'left' => 3.0],
            'content_frame' => [
                'enabled' => false,
                'width_mm' => null,
                'height_mm' => null,
                'alignment' => 'top-center',
                'offset_x_mm' => 0.0,
                'offset_y_mm' => 0.0,
            ],
            'scale_percent' => 100,
            'base_font_size_px' => 7,
            'fit_to_page' => false,
            'repeat_table_headers' => false,
            'show_page_numbers' => false,
            'capabilities' => [
                'orientation' => false,
                'content_frame' => false,
                'fit_to_page' => false,
                'custom_size' => false,
            ],
        ],
        'a5_portrait' => [
            'label' => 'A5 Compact',
            'paper' => 'a5',
            'orientation' => 'portrait',
            'margins_mm' => ['top' => 8.0, 'right' => 8.0, 'bottom' => 8.0, 'left' => 8.0],
            'content_frame' => [
                'enabled' => false,
                'width_mm' => null,
                'height_mm' => null,
                'alignment' => 'top-center',
                'offset_x_mm' => 0.0,
                'offset_y_mm' => 0.0,
            ],
            'scale_percent' => 100,
            'base_font_size_px' => 9,
            'fit_to_page' => true,
            'repeat_table_headers' => true,
            'show_page_numbers' => false,
            'capabilities' => [
                'orientation' => true,
                'content_frame' => false,
                'fit_to_page' => true,
                'custom_size' => false,
            ],
        ],
        'standard_report' => [
            'label' => 'Standard Report (A4 Portrait)',
            'paper' => 'a4',
            'orientation' => 'portrait',
            'margins_mm' => ['top' => 9.26, 'right' => 9.26, 'bottom' => 9.26, 'left' => 9.26],
            'content_frame' => [
                'enabled' => false,
                'width_mm' => null,
                'height_mm' => null,
                'alignment' => 'top-center',
                'offset_x_mm' => 0.0,
                'offset_y_mm' => 0.0,
            ],
            'scale_percent' => 100,
            'base_font_size_px' => 10,
            'fit_to_page' => false,
            'repeat_table_headers' => true,
            'show_page_numbers' => true,
            'capabilities' => [
                'orientation' => true,
                'content_frame' => false,
                'fit_to_page' => false,
                'custom_size' => false,
            ],
        ],
        'wide_report' => [
            'label' => 'Wide Report (A4 Landscape)',
            'paper' => 'a4',
            'orientation' => 'landscape',
            'margins_mm' => ['top' => 9.26, 'right' => 9.26, 'bottom' => 9.26, 'left' => 9.26],
            'content_frame' => [
                'enabled' => false,
                'width_mm' => null,
                'height_mm' => null,
                'alignment' => 'top-center',
                'offset_x_mm' => 0.0,
                'offset_y_mm' => 0.0,
            ],
            'scale_percent' => 100,
            'base_font_size_px' => 10,
            'fit_to_page' => false,
            'repeat_table_headers' => true,
            'show_page_numbers' => true,
            'capabilities' => [
                'orientation' => true,
                'content_frame' => false,
                'fit_to_page' => false,
                'custom_size' => false,
            ],
        ],
        'custom' => [
            'label' => 'Custom Size',
            'paper' => 'custom',
            'orientation' => 'portrait',
            'margins_mm' => ['top' => 9.26, 'right' => 9.26, 'bottom' => 9.26, 'left' => 9.26],
            'content_frame' => [
                'enabled' => false,
                'width_mm' => null,
                'height_mm' => null,
                'alignment' => 'top-center',
                'offset_x_mm' => 0.0,
                'offset_y_mm' => 0.0,
            ],
            'scale_percent' => 100,
            'base_font_size_px' => 10,
            'fit_to_page' => false,
            'repeat_table_headers' => true,
            'show_page_numbers' => true,
            'capabilities' => [
                'orientation' => true,
                'content_frame' => true,
                'fit_to_page' => true,
                'custom_size' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Report-Specific Default Presets & Allowed Presets
    |--------------------------------------------------------------------------
    */
    'reports' => [
        'appointment_invoice' => [
            'label' => 'Appointment Invoice',
            'default_preset' => 'compact_100x150',
            'allowed_presets' => ['compact_100x150', 'receipt_on_a4', 'thermal_80', 'thermal_58', 'a5_portrait', 'standard_report', 'custom'],
        ],
        'daily_sales' => [
            'label' => 'Daily Sales Report',
            'default_preset' => 'standard_report',
            'allowed_presets' => ['standard_report', 'wide_report', 'a5_portrait', 'custom'],
        ],
        'appointment_report' => [
            'label' => 'Appointment Report',
            'default_preset' => 'wide_report',
            'allowed_presets' => ['wide_report', 'standard_report', 'custom'],
        ],
        'attendance_report' => [
            'label' => 'Attendance Report',
            'default_preset' => 'wide_report',
            'allowed_presets' => ['wide_report', 'standard_report', 'custom'],
        ],
        'room_report' => [
            'label' => 'Room Report',
            'default_preset' => 'standard_report',
            'allowed_presets' => ['standard_report', 'wide_report', 'custom'],
        ],
        'service_popularity' => [
            'label' => 'Service Popularity Report',
            'default_preset' => 'standard_report',
            'allowed_presets' => ['standard_report', 'wide_report', 'custom'],
        ],
        'staff_utilization' => [
            'label' => 'Staff Utilization Report',
            'default_preset' => 'wide_report',
            'allowed_presets' => ['wide_report', 'standard_report', 'custom'],
        ],
        'weekly_schedule' => [
            'label' => 'Weekly Therapist Schedule',
            'default_preset' => 'wide_report',
            'allowed_presets' => ['wide_report', 'standard_report', 'custom'],
        ],
        'business_report' => [
            'label' => 'Business Report',
            'default_preset' => 'wide_report',
            'allowed_presets' => ['wide_report', 'standard_report', 'custom'],
        ],
        'staff_commissions' => [
            'label' => 'Staff Commission Statement',
            'default_preset' => 'wide_report',
            'allowed_presets' => ['wide_report', 'standard_report', 'custom'],
        ],
    ],
];
