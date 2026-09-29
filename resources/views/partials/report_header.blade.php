<div class="report-header">
    <div class="header-top">
        <div class="brand">
            <h1>SPA ALEXANDRIA</h1>
            <div class="tagline">{{ $tagline ?? 'ZenFlow Appointment & Workforce System' }}</div>
        </div>
        <div class="report-meta-badge">
            <div class="label">{{ $reportTypeLabel ?? 'Report Type' }}</div>
            <div class="value">{{ $reportTitle }}</div>
        </div>
    </div>
    <div class="header-period">
        <div class="date-range">{{ $dateRange }}</div>
        <div class="meta">Generated {{ $generatedAt }} &bull; Prepared by {{ $preparedBy }}</div>
    </div>
    <div class="gold-line"></div>
</div>
<style>
    /* Base Styles */
    body {
        font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
        color: #1e293b;
        line-height: 1.5;
        background: #ffffff;
    }

    /* Report Header */
    .report-header {
        background: #0f766e; /* Teal-700 */
        color: white;
        padding: 30px 35px 25px;
        position: relative;
    }
    .report-header .gold-line {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: #fbbf24; /* Gold accent */
    }
    .header-top {
        margin-bottom: 20px;
        overflow: hidden; /* Clearfix */
    }
    .brand {
        float: left;
    }
    .brand h1 {
        font-size: 22px;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .brand .tagline {
        font-size: 9px;
        opacity: 0.7;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-top: 3px;
    }
    .report-meta-badge {
        float: right;
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 8px;
        padding: 8px 16px;
        text-align: center;
    }
    .report-meta-badge .label {
        font-size: 7px;
        text-transform: uppercase;
        letter-spacing: 2px;
        opacity: 0.7;
        margin-bottom: 2px;
    }
    .report-meta-badge .value {
        font-size: 11px;
        font-weight: 700;
    }

    .header-period {
        text-align: center;
        padding-top: 10px;
    }
    .header-period .date-range {
        font-size: 15px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .header-period .meta {
        font-size: 9px;
        opacity: 0.6;
        margin-top: 5px;
    }
</style>