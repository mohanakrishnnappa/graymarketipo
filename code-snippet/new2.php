
/*
Plugin Name: Grey Market IPO Table
Description: Displays Grey Market IPO data in clean professional format with separate Profit/Loss column and sorting functionality
*/

// Register shortcode
add_shortcode('graymarket_ipo_table', 'graymarket_ipo_table_shortcode');

function graymarket_ipo_table_shortcode($atts) {
    // Get saved data from database
    $ipo_data = get_option('graymarket_ipo_data', array());
    
    // Update status for all entries automatically
    $ipo_data = graymarket_update_all_statuses($ipo_data);
    
    // Get current sort settings and apply sorting
    $primary_sort = get_option('graymarket_primary_sort', 'status_priority');
    $secondary_sort = get_option('graymarket_secondary_sort', 'none');
    
    // Apply sorting
    $ipo_data = graymarket_sort_ipo_data($ipo_data, $primary_sort, $secondary_sort);
    
    // **NEW ADDITION: Count items for each filter category**
    // Initialise filter counts to avoid undefined key warnings
    $filter_counts = array(
        'all' => 0,
        'upcoming' => 0,
        'open' => 0,
        'closing-today' => 0,
        'close' => 0,
        'listed' => 0,
        'archived' => 0
    );

    // Now count each status
    foreach ($ipo_data as $ipo) {
        if (($ipo['archived'] ?? 'no') === 'yes') {
            $filter_counts['archived']++;
        } else {
            $filter_counts['all']++;
            $status = strtolower(str_replace(' ', '-', $ipo['status'] ?? 'upcoming'));
            if (isset($filter_counts[$status])) {
                $filter_counts[$status]++;
            }
        }
    }
    
    ob_start();
    ?>
    <style>
    .graymarket-header {
        margin-bottom: 30px;
        text-align: center;
    }
    
    .graymarket-header h2 {
        margin: 0;
        color: #2c3e50;
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 10px;
    }
    
    .graymarket-header::after {
        content: '';
        display: block;
        width: 60px;
        height: 3px;
        background: #3498db;
        margin: 0 auto;
        border-radius: 2px;
    }
    
    /* Search and Filter Styles */
    .graymarket-search-filter {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e1e5e9;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    input.graymarket-search-bar {
        border-radius: 5px !important;
    }
    
    .graymarket-search-bar {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 16px;
        margin-bottom: 15px;
        box-sizing: border-box;
    }
    
    .graymarket-search-bar:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
    }
    
    .graymarket-filter-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .graymarket-filter-btn {
        padding: 8px 16px;
        border: 1px solid #3498db;
        background: white;
        color: black;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .graymarket-filter-btn:hover {
        background: black;
        color: white;
        border-color: #ddd;
        transition: all 0.2s ease;
    }
    
    .graymarket-filter-btn.active {
        background: #3498db;
        color: white;
        border-color: #3498db;
    }
    
    .graymarket-filter-btn.upcoming.active { background: #F9C10B; border-color: #F9C10B; }
    .graymarket-filter-btn.open.active { background: #278754; border-color: #278754; }
    .graymarket-filter-btn.closing-today.active { background: #F3627A; border-color: #F3627A; }
    .graymarket-filter-btn.close.active { background: #FF4500; border-color: #FF4500; }
    .graymarket-filter-btn.listed.active { background: #6F42C1; border-color: #6F42C1; }
    
    /* NEW CSS for count styling */
    .graymarket-filter-count {
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        margin-left: 5px;
        background-color: #3498db;
        color: white;
    }
    
    .graymarket-filter-btn.active .graymarket-filter-count {
        background: rgba(255,255,255,0.3);
    }

    /* =====  TYPE FILTER (Front-End)===== */

    .graymarket-type-filter {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        justify-content: center;
        margin: 10px 0 15px;
    }

    .graymarket-type-filter .graymarket-type-filter-title {
        font-weight: 600;
        color: #333;
        margin-right: 10px;
    }

    /* Hide native checkbox */
    .graymarket-type-filter input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    /* Label styling - unified */
    .graymarket-type-filter label {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: #fff;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s;
    }

    .graymarket-type-filter label:hover {
        background: #f8f9fa;
        border-color: #3498db;
    }

    /* Custom checkbox square */
    .graymarket-type-filter label::before {
        content: "";
        width: 16px;
        height: 16px;
        border: 2px solid #bbb;
        border-radius: 3px;
        background: white;
        display: inline-block;
        transition: border-color 0.2s, background 0.2s;
    }

    /* Checked State with tick icon */
    .graymarket-type-filter input[type="checkbox"]:checked + span::before,
    .graymarket-type-filter label:has(input[type="checkbox"]:checked)::before {
        background: #3498db;
        border-color: #3498db;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 12 10' xmlns='http://www.w3.org/2000/svg'%3E%3Cpolyline points='1.5 6 4.5 9 10.5 1' stroke='white' stroke-width='2' fill='none'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: center;
    }

    .graymarket-type-filter span {
        position: relative;
        padding-left: 0;
    }

    /* ================================
    RESPONSIVE
    ================================ */

    @media (max-width: 768px) {
        .graymarket-type-filter {
            justify-content: flex-start;
        }

        .graymarket-type-filter label {
            font-size: 13px;
            padding: 5px 10px;
        }

        .graymarket-type-filter label::before {
            width: 14px;
            height: 14px;
        }
    }
    
    /* Rest of your existing CSS remains the same */
    .graymarket-no-results {
        text-align: center;
        padding: 40px 20px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e1e5e9;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-top: 20px;
    }
    
    .graymarket-no-data {
        text-align: center;
        padding: 40px 20px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e1e5e9;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .graymarket-no-data p {
        font-size: 16px;
        color: #6c757d;
        margin: 10px 0;
    }
    
    .graymarket-no-data a {
        color: #3498db;
        text-decoration: none;
        font-weight: 500;
        padding: 10px 20px;
        background: #3498db;
        color: white;
        border-radius: 5px;
        display: inline-block;
        transition: background-color 0.2s ease;
    }
    
    .graymarket-no-data a:hover {
        background: #2980b9;
    }
    
    .graymarket-plain-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .graymarket-ipo-item {
        background: white;
        border-radius: 8px;
        border: 2px solid #e1e5e9;
        border-bottom: 3px solid #3498db;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: box-shadow 0.2s ease;
    }
    
    .graymarket-ipo-item:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .graymarket-item-header {
        text-align: center;
        padding: 25px;
        background: #ffffff;
        border-bottom: 1px solid #e9ecef;
    }
    
    .graymarket-company-logo {
        width: auto;
        height: 80px;
        max-width: 120px;
        border-radius: 6px;
        object-fit: contain;
        border: 1px solid #e9ecef;
        background: #ffffff;
        padding: 10px;
    }
    
    .graymarket-item-content {
        padding: 5px 25px;
    }
    
    .graymarket-item-content p {
        margin: 15px 0;
        font-size: 15px;
        line-height: 1.5;
        color: #495057;
        border-bottom: 1px solid #f1f3f4;
        padding-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .graymarket-item-content p:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .graymarket-item-content strong {
        color: #343a40;
        font-weight: 600;
        font-size: 14px;
        min-width: 130px;
    }
    
    .graymarket-gmp {
        color: #0066cc !important;
        font-weight: 600 !important;
        font-size: 16px !important;
    }
    
    .graymarket-price {
        color: #28a745 !important;
        font-weight: 600 !important;
        font-size: 16px !important;
    }
    
    .graymarket-estimated-price {
        color: #fd7e14 !important;
        font-weight: 600 !important;
        font-size: 16px !important;
    }
    
    .graymarket-listed-price {
        color: #17a2b8 !important;
        font-weight: 600 !important;
        font-size: 16px !important;
    }
    
    .graymarket-gain {
        font-weight: 600 !important;
        font-size: 14px !important;
        padding: 4px 12px !important;
        border-radius: 4px !important;
        text-align: center;
        min-width: 70px;
    }
    
    .graymarket-gain.positive {
        background-color: #d4edda !important;
        color: #155724 !important;
        border: 1px solid #c3e6cb !important;
    }
    
    .graymarket-gain.negative {
        background-color: #f8d7da !important;
        color: #721c24 !important;
        border: 1px solid #f5c6cb !important;
    }
    
    .graymarket-gain.neutral {
        background-color: #e2e3e5 !important;
        color: #383d41 !important;
        border: 1px solid #d6d8db !important;
    }
    
    .graymarket-status {
        color: white !important;
        padding: 4px 12px !important;
        border-radius: 4px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .graymarket-status.upcoming {
        background: #F9C10B !important;
    }

    .graymarket-status.open {
        background: #278754 !important;
    }
    
    .graymarket-status.close {
        background: #FF4500 !important;
    }
    
    .graymarket-status.closing-today {
        background: #F3627A !important;
    }
    
    .graymarket-status.listed {
        background: #6F42C1 !important;
    }
    
    .graymarket-allotted {
        color: white !important;
        padding: 2px 8px !important;
        border-radius: 4px !important;
        font-size: 10px !important;
        font-weight: 500 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        background: #17a2b8 !important;
        margin-left: 5px !important;
        display: inline-block !important;
    }
    
    .graymarket-type {
        color: white !important;
        padding: 4px 12px !important;
        border-radius: 4px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .graymarket-ipo-item .graymarket-type {
        background: #6f42c1 !important;
    }
    
    .graymarket-ipo-item:has(span:contains("NSE SME")) .graymarket-type,
    .graymarket-ipo-item span[class*="graymarket-type"]:contains("NSE SME") {
        background: #007bff !important;
    }
    
    .graymarket-ipo-item:has(span:contains("BSE SME")) .graymarket-type,
    .graymarket-ipo-item span[class*="graymarket-type"]:contains("BSE SME") {
        background: #28a745 !important;
    }
    
    span.graymarket-type[data-type="NSE SME"] {
        background: #007bff !important;
    }
    
    span.graymarket-type[data-type="BSE SME"] {
        background: #28a745 !important;
    }
    
    .graymarket-read-more-btn {
        background: #3498db !important;
        color: white !important;
        padding: 8px 16px !important;
        border-radius: 5px !important;
        text-decoration: none !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        display: inline-block !important;
        transition: background-color 0.2s ease !important;
    }
    
    .graymarket-read-more-btn:hover {
        background: #2980b9 !important;
        color: white !important;
        text-decoration: none !important;
    }
    
    .graymarket-read-more-center {
        text-align: center !important;
        justify-content: center !important;
    }
    
    /* Admin specific styles */
    .graymarket-add-new-section {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .graymarket-list-item {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
        position: relative;
    }
    
    .graymarket-list-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .graymarket-header h2 {
            font-size: 24px;
        }
        
        .graymarket-plain-container {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .graymarket-ipo-item {
            margin: 0 5px;
        }
        
        .graymarket-item-header {
            padding: 20px;
        }
        
        .graymarket-company-logo {
            height: 60px;
            max-width: 100px;
        }
        
        .graymarket-item-content {
            padding: 5px 20px;
        }
        
        .graymarket-item-content p {
            font-size: 16px;
            margin: 12px 0;
            padding-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .graymarket-item-content strong {
            min-width: 110px;
            font-size: 15px;
        }
        
        .graymarket-gmp,
        .graymarket-price,
        .graymarket-estimated-price,
        .graymarket-listed-price {
            font-size: 17px !important;
        }
        
        .graymarket-gain {
            font-size: 15px !important;
        }
        
        .graymarket-filter-buttons {
            justify-content: flex-start;
        }
        
        .graymarket-filter-btn {
            font-size: 12px;
            padding: 6px 12px;
        }
    }
    
    @media (max-width: 480px) {
        .graymarket-header h2 {
            font-size: 20px;
        }
        
        .graymarket-item-content {
            padding: 5px 15px;
        }
        
        .graymarket-company-logo {
            height: 50px;
            max-width: 80px;
        }
        
        .graymarket-item-content p {
            font-size: 15px;
            margin: 10px 0;
            padding-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .graymarket-item-content strong {
            font-size: 14px;
            min-width: 100px;
        }
        
        .graymarket-gmp,
        .graymarket-price,
        .graymarket-estimated-price,
        .graymarket-listed-price,
        .graymarket-gain {
            font-size: 16px !important;
        }
    }
    
    /* Custom CSS */
    .graymarket-company-name {
        text-align: right !important;
        word-wrap: break-word;
        hyphens: auto;
    }
    
    @media screen and (max-width: 480px) {
        .graymarket-company-name {
            text-align: right !important;
            word-wrap: break-word !important;
            hyphens: auto;
            white-space: normal !important;
            overflow: visible !important;
            flex: 1;
            margin-left: 10px;
            display: inline-block !important;
            line-height: 1.3;
            max-width: 100%;
        }
    }
    </style>
    
    <div class="graymarket-ipo-container">
        <div class="graymarket-header">
            <h2><strong>Gray Market IPO List</strong></h2>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="graymarket-search-filter">
            <input type="text" 
                   id="graymarket-search" 
                   class="graymarket-search-bar" 
                   placeholder="Search IPO by Company Name... 🔍" />
            
            <!-- UPDATED FILTER BUTTONS with counts -->
            <div class="graymarket-filter-buttons">
                <button class="graymarket-filter-btn active" data-filter="all">
                    All <span class="graymarket-filter-count"><?php echo $filter_counts['all']; ?></span>
                </button>
                <button class="graymarket-filter-btn upcoming" data-filter="upcoming">
                    Upcoming <span class="graymarket-filter-count"><?php echo $filter_counts['upcoming']; ?></span>
                </button>
                <button class="graymarket-filter-btn open" data-filter="open">
                    Open <span class="graymarket-filter-count"><?php echo $filter_counts['open']; ?></span>
                </button>
                <button class="graymarket-filter-btn closing-today" data-filter="closing-today">
                    Closing Today <span class="graymarket-filter-count"><?php echo $filter_counts['closing-today']; ?></span>
                </button>
                <button class="graymarket-filter-btn close" data-filter="close">
                    Close <span class="graymarket-filter-count"><?php echo $filter_counts['close']; ?></span>
                </button>
                <button class="graymarket-filter-btn listed" data-filter="listed">
                    Listed <span class="graymarket-filter-count"><?php echo $filter_counts['listed']; ?></span>
                </button>
                <button class="graymarket-filter-btn archived" data-filter="archived">
                    Archived <span class="graymarket-filter-count"><?php echo $filter_counts['archived']; ?></span>
                </button>
            </div>

            <!-- NEW: Type Filter Checkboxes -->
            <div class="graymarket-type-filter">
                <span class="graymarket-type-filter-title">Type Filter:</span>
                <label>
                    <input type="checkbox" id="mainboard-filter" checked>
                    <span>Mainboard</span>
                </label>
                <label>
                    <input type="checkbox" id="sme-filter" checked>
                    <span>SME</span>
                </label>
            </div>
        </div>
        
        <?php if (empty($ipo_data)): ?>
            <div class="graymarket-no-data">
                <p>No IPO data available. Please add data from the admin panel.</p>
                <?php if (current_user_can('manage_options')): ?>
                    <p><a href="<?php echo admin_url('admin.php?page=graymarket-ipo-admin'); ?>">Add IPO Data</a></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="graymarket-plain-container" id="graymarket-container">
            <?php foreach ($ipo_data as $index => $ipo): ?>
            <div class="graymarket-ipo-item" 
                data-name="<?php echo esc_attr(strtolower($ipo['name'])); ?>"
                data-status="<?php echo esc_attr(strtolower(str_replace(' ', '-', $ipo['status'] ?? 'upcoming'))); ?>"
                data-type="<?php echo esc_attr(strtolower($ipo['type'] ?? 'mainboard')); ?>"
                 data-archived="<?php echo esc_attr($ipo['archived'] ?? 'no'); ?>">
                <div class="graymarket-item-header">
                    <img src="<?php echo esc_url($ipo['logo']); ?>" alt="<?php echo esc_attr($ipo['name']); ?>" class="graymarket-company-logo">
                </div>
                
                <div class="graymarket-item-content">
                    <p class="graymarket-company-name-row"><strong>Company Name:</strong> <span class="graymarket-company-name"><?php echo esc_html($ipo['name']); ?></span></p>
                    <p><strong>Status:</strong> 
                        <span>
                            <span class="graymarket-status <?php echo strtolower(str_replace(' ', '-', $ipo['status'] ?? 'upcoming')); ?>"><?php echo esc_html($ipo['status'] ?? 'Upcoming'); ?></span>
                            <?php if (($ipo['status'] ?? '') === 'Close' && !empty($ipo['allotted']) && $ipo['allotted'] === 'yes'): ?>
                                <span class="graymarket-allotted">Allotted</span>
                            <?php endif; ?>
                        </span>
                    </p>
                    <p><strong>IPO GMP:</strong> <span class="graymarket-gmp"><?php echo empty($ipo['gmp']) || $ipo['gmp'] === '' ? 'N/A' : '₹' . esc_html($ipo['gmp']); ?></span></p>
                    <p><strong>Price:</strong> <span class="graymarket-price"><?php echo empty($ipo['price']) || $ipo['price'] === '' ? 'N/A' : '₹' . esc_html($ipo['price']); ?></span></p>
                    
                    <?php if (($ipo['status'] ?? '') === 'Listed'): ?>
                    <p><strong>Listed Price:</strong> <span class="graymarket-listed-price"><?php echo empty($ipo['listed_price']) || $ipo['listed_price'] === '' ? 'N/A' : '₹' . esc_html($ipo['listed_price']); ?></span></p>
                    <p><strong>Profit/Loss:</strong> <span class="graymarket-gain <?php echo $ipo['listed_gain_class'] ?? 'neutral'; ?>"><?php echo esc_html($ipo['listed_gain'] ?? '0'); ?>%</span></p>
                    <?php else: ?>
                    <p><strong>Estimated Price:</strong> <span class="graymarket-estimated-price"><?php echo (empty($ipo['estimated_price']) || $ipo['estimated_price'] === '' || $ipo['estimated_price'] === 0) ? 'N/A' : '₹' . esc_html($ipo['estimated_price']); ?></span></p>
                    <p><strong>Profit/Loss:</strong> <span class="graymarket-gain <?php echo $ipo['gain_class']; ?>"><?php echo esc_html($ipo['gain']); ?>%</span></p>
                    <?php endif; ?>
                    
                    <p><strong>IPO Size:</strong> <span><?php echo empty($ipo['ipo_size']) || $ipo['ipo_size'] === '' ? '₹N/A Cr' : '₹' . esc_html($ipo['ipo_size']) . ' Cr'; ?></span></p>
                    <p><strong>Lot Size:</strong> <span><?php echo empty($ipo['lot']) || $ipo['lot'] === '' ? 'N/A Shares' : esc_html($ipo['lot']) . ' Shares'; ?></span></p>
                    <p><strong>Open Date:</strong> <span><?php echo esc_html(graymarket_format_date_display($ipo['open_date'] ?? '')); ?></span></p>
                    <p><strong>Close Date:</strong> <span><?php echo esc_html(graymarket_format_date_display($ipo['close_date'] ?? '')); ?></span></p>
                    <p><strong>BoA Date:</strong> <span><?php echo esc_html(graymarket_format_date_display($ipo['boa_date'] ?? '')); ?></span></p>
                    <p><strong>Listing Date:</strong> <span><?php echo esc_html(graymarket_format_date_display($ipo['listing_date'] ?? '')); ?></span></p>
                    <p><strong>Type:</strong> <span class="graymarket-type graymarket-type-<?php echo strtolower(str_replace(' ', '-', $ipo['type'] ?? 'mainboard')); ?>" data-type="<?php echo esc_attr($ipo['type'] ?? 'Mainboard'); ?>"><?php echo esc_html($ipo['type'] ?? 'Mainboard'); ?></span></p>
                    <?php if (!empty($ipo['read_more_link'])): ?>
                    <p class="graymarket-read-more-center"><a href="<?php echo esc_url($ipo['read_more_link']); ?>" class="graymarket-read-more-btn" target="_blank">Read More Details</a></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- No Results Message -->
        <div class="graymarket-no-results" id="graymarket-no-results" style="display: none;">
            <p>No IPOs found matching your search criteria.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    // JavaScript to apply colors based on type content and handle search/filter
    document.addEventListener('DOMContentLoaded', function() {
        const typeElements = document.querySelectorAll('.graymarket-type');
        typeElements.forEach(function(element) {
            const typeText = element.textContent.trim();
            if (typeText === 'NSE SME') {
                element.style.backgroundColor = '#007bff';
            } else if (typeText === 'BSE SME') {
                element.style.backgroundColor = '#28a745';
            } else if (typeText === 'Mainboard') {
                element.style.backgroundColor = '#6f42c1';
            }
        });
        
        // Search and Filter functionality
        const searchInput = document.getElementById('graymarket-search');
        const filterButtons = document.querySelectorAll('.graymarket-filter-btn');
        const ipoItems = document.querySelectorAll('.graymarket-ipo-item');
        const container = document.getElementById('graymarket-container');
        const noResults = document.getElementById('graymarket-no-results');
        
        // NEW: Type filter checkboxes
        const mainboardFilter = document.getElementById('mainboard-filter');
        const smeFilter = document.getElementById('sme-filter');
        
        let currentFilter = 'all';
        let currentSearch = '';
        
        // Search functionality
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                currentSearch = this.value.toLowerCase().trim();
                filterItems();
            });
        }
        
        // Filter functionality
        filterButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                filterButtons.forEach(function(b) {
                    b.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                currentFilter = this.dataset.filter;
                filterItems();
            });
        });
        
        // NEW: Type filter functionality
        if (mainboardFilter && smeFilter) {
            mainboardFilter.addEventListener('change', filterItems);
            smeFilter.addEventListener('change', filterItems);
        }
        
        function filterItems() {
            let visibleCount = 0;
            
            ipoItems.forEach(function(item) {
                const name = item.dataset.name || '';
                const status = item.dataset.status || '';
                const type = item.dataset.type || '';
                
                // Check search match
                const searchMatch = currentSearch === '' || name.includes(currentSearch);
                
                // Check status filter match
                let statusMatch = false;
                if (currentFilter === 'archived') {
                    statusMatch = item.dataset.archived === 'yes';
                } else if (currentFilter === 'all') {
                    statusMatch = item.dataset.archived !== 'yes';
                } else {
                    statusMatch = status === currentFilter && item.dataset.archived !== 'yes';
                }
                
                // NEW: Check type filter match
                let typeMatch = false;
                const isMainboardChecked = mainboardFilter ? mainboardFilter.checked : true;
                const isSmeChecked = smeFilter ? smeFilter.checked : true;
                
                if (isMainboardChecked && isSmeChecked) {
                    // Both checked - show all
                    typeMatch = true;
                } else if (isMainboardChecked && !isSmeChecked) {
                    // Only Mainboard checked
                    typeMatch = type === 'mainboard';
                } else if (!isMainboardChecked && isSmeChecked) {
                    // Only SME checked - show both NSE SME and BSE SME
                    typeMatch = type === 'nse sme' || type === 'bse sme';
                } else {
                    // Neither checked - hide all
                    typeMatch = false;
                }
                
                // Show/hide item
                if (searchMatch && statusMatch && typeMatch) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            if (visibleCount === 0) {
                if (container) container.style.display = 'none';
                if (noResults) noResults.style.display = 'block';
            } else {
                if (container) container.style.display = 'grid';
                if (noResults) noResults.style.display = 'none';
            }
        }
        // Run once on page load to apply default filter (exclude archived from "All")
        filterItems();
    });
    </script>
    <?php
    return ob_get_clean();
}

// Add sorting functions
function graymarket_sort_ipo_data($ipo_data, $primary_sort = 'status_priority', $secondary_sort = 'none') {
    if (empty($ipo_data)) {
        return $ipo_data;
    }
    
    // Get manual order if exists
    $manual_order = get_option('graymarket_manual_order', array());
    
    usort($ipo_data, function($a, $b) use ($primary_sort, $secondary_sort, $manual_order) {
        $result = graymarket_compare_entries($a, $b, $primary_sort, $manual_order);
        
        // If primary sort is equal and secondary sort is specified
        if ($result === 0 && $secondary_sort !== 'none') {
            $result = graymarket_compare_entries($a, $b, $secondary_sort, $manual_order);
        }
        
        return $result;
    });
    
    return $ipo_data;
}

// Compare function for sorting
function graymarket_compare_entries($a, $b, $sort_type, $manual_order = array()) {
    switch ($sort_type) {
        case 'status_priority':
        $status_priority = array(
            'Upcoming' => 1,
            'Open' => 2,
            'Closing Today' => 3,
            'Close' => 4,
            'Listed' => 5,
            'Archived' => 6
        );

        // Default priorities based on status
        $a_status = $a['status'] ?? 'Upcoming';
        $b_status = $b['status'] ?? 'Upcoming';

        $a_priority = $status_priority[$a_status] ?? 5; // default to Listed weight if unknown
        $b_priority = $status_priority[$b_status] ?? 5;

        // If archived flag is set, force to Archived (6)
        if (($a['archived'] ?? 'no') === 'yes') {
            $a_priority = 6;
        }
        if (($b['archived'] ?? 'no') === 'yes') {
            $b_priority = 6;
        }

        return $a_priority - $b_priority;
            
        case 'name_asc':
            return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
            
        case 'name_desc':
            return strcasecmp($b['name'] ?? '', $a['name'] ?? '');
            
        case 'listing_date_desc':
            $a_date = strtotime($a['listing_date'] ?? '1970-01-01');
            $b_date = strtotime($b['listing_date'] ?? '1970-01-01');
            return $b_date - $a_date;
            
        case 'listing_date_asc':
            $a_date = strtotime($a['listing_date'] ?? '1970-01-01');
            $b_date = strtotime($b['listing_date'] ?? '1970-01-01');
            return $a_date - $b_date;
            
        case 'close_date_desc':
            $a_date = strtotime($a['close_date'] ?? '1970-01-01');
            $b_date = strtotime($b['close_date'] ?? '1970-01-01');
            return $b_date - $a_date;
            
        case 'close_date_asc':
            $a_date = strtotime($a['close_date'] ?? '1970-01-01');
            $b_date = strtotime($b['close_date'] ?? '1970-01-01');
            return $a_date - $b_date;
            
        case 'open_date_desc':
            $a_date = strtotime($a['open_date'] ?? '1970-01-01');
            $b_date = strtotime($b['open_date'] ?? '1970-01-01');
            return $b_date - $a_date;
            
        case 'open_date_asc':
            $a_date = strtotime($a['open_date'] ?? '1970-01-01');
            $b_date = strtotime($b['open_date'] ?? '1970-01-01');
            return $a_date - $b_date;
            
        case 'gmp_desc':
            $a_gmp = floatval($a['gmp'] ?? 0);
            $b_gmp = floatval($b['gmp'] ?? 0);
            return $b_gmp <=> $a_gmp;
            
        case 'gmp_asc':
            $a_gmp = floatval($a['gmp'] ?? 0);
            $b_gmp = floatval($b['gmp'] ?? 0);
            return $a_gmp <=> $b_gmp;
            
        case 'manual':
            if (!empty($manual_order)) {
                $a_index = array_search($a['name'] ?? '', $manual_order);
                $b_index = array_search($b['name'] ?? '', $manual_order);
                
                if ($a_index !== false && $b_index !== false) {
                    return $a_index - $b_index;
                } elseif ($a_index !== false) {
                    return -1;
                } elseif ($b_index !== false) {
                    return 1;
                }
            }
            return 0;
            
        default:
            return 0;
    }
}

// **UPDATED FUNCTION**: Automatic status calculation based on IST dates
function graymarket_calculate_status($open_date, $close_date, $listing_date) {
    // Set timezone to India Standard Time
    date_default_timezone_set('Asia/Kolkata');
    $today = date('Y-m-d');
    $current_time = date('H:i');
    
    // Convert dates to Y-m-d format if needed
    $open_date = graymarket_normalize_date($open_date);
    $close_date = graymarket_normalize_date($close_date);
    $listing_date = graymarket_normalize_date($listing_date);
    
    // Check listing date first (highest priority)
    if (!empty($listing_date) && $listing_date !== 'N/A') {
        if ($today >= $listing_date) {
            return 'Listed';
        }
    }
    
    // Check close date
    if (!empty($close_date) && $close_date !== 'N/A') {
        if ($today > $close_date) {
            return 'Close';
        } elseif ($today === $close_date) {
            // UPDATED: Change to "Closing Today" immediately when it's the close date
            if ($current_time >= '15:30') {
                return 'Close';
            } else {
                return 'Closing Today';
            }
        }
    }
    
    // Check open date
    if (!empty($open_date) && $open_date !== 'N/A') {
        if ($today === $open_date) {
            // UPDATED: Change to "Open" immediately when it's the open date (no need to wait for 9:15)
            return 'Open';
        } elseif ($today > $open_date && (!empty($close_date) && $today < $close_date)) {
            return 'Open';
        } elseif ($today < $open_date) {
            return 'Upcoming';
        }
    }
    
    // Default status
    return 'Upcoming';
}

// **NEW FUNCTION**: Normalize date format
function graymarket_normalize_date($date) {
    if (empty($date) || $date === 'N/A') {
        return '';
    }
    
    // If already in Y-m-d format, return as is
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return $date;
    }
    
    // Try to convert various date formats to Y-m-d
    $timestamp = strtotime($date);
    if ($timestamp) {
        return date('Y-m-d', $timestamp);
    }
    
    return '';
}

// **NEW FUNCTION**: Update all statuses automatically
function graymarket_update_all_statuses($ipo_data) {
    if (empty($ipo_data)) {
        return $ipo_data;
    }
    
    $updated = false;
    foreach ($ipo_data as $index => $ipo) {
        $new_status = graymarket_calculate_status(
            $ipo['open_date'] ?? '',
            $ipo['close_date'] ?? '',
            $ipo['listing_date'] ?? ''
        );
        
        if (($ipo['status'] ?? '') !== $new_status) {
            $ipo_data[$index]['status'] = $new_status;
            $updated = true;
        }
        
        // Calculate listed gain if status is Listed and listed_price is set
        if ($new_status === 'Listed' && !empty($ipo['listed_price'])) {
            $listed_price = floatval($ipo['listed_price']);
            $price = floatval($ipo['price']);
            
            if ($price > 0) {
                $listed_gain = round((($listed_price - $price) / $price) * 100, 2);
                $listed_gain_class = 'neutral';
                if ($listed_gain > 0) {
                    $listed_gain_class = 'positive';
                } elseif ($listed_gain < 0) {
                    $listed_gain_class = 'negative';
                }
                
                $ipo_data[$index]['listed_gain'] = $listed_gain;
                $ipo_data[$index]['listed_gain_class'] = $listed_gain_class;
            }
        }
    }
    
    // Update database if any status changed
    if ($updated) {
        update_option('graymarket_ipo_data', $ipo_data);
    }
    
    return $ipo_data;
}

// Helper function to format dates for display
function graymarket_format_date_display($date) {
    if (empty($date) || $date === 'N/A') {
        return 'N/A';
    }
    
    // If it's already in a readable format, return as is
    if (strpos($date, '-') !== false && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return $date;
    }
    
    // Convert date to readable format
    $timestamp = strtotime($date);
    if ($timestamp) {
        return date('d-F-Y', $timestamp);
    }
    
    return $date; // Return original if conversion fails
}

// Continue with the rest of your existing admin functions...
// [Rest of the admin functions remain the same as in your original code]

// Add admin menu
add_action('admin_menu', 'graymarket_admin_menu');

function graymarket_admin_menu() {
    add_menu_page(
        'Grey Market IPO',
        'Grey Market IPO',
        'manage_options',
        'graymarket-ipo-admin',
        'graymarket_admin_page',
        'dashicons-chart-line',
        30
    );
    
    // Add Settings submenu
    add_submenu_page(
        'graymarket-ipo-admin',
        'Settings',
        'Settings',
        'manage_options',
        'graymarket-ipo-settings',
        'graymarket_settings_page'
    );
}

// Helper function to get logo URL with default fallback from settings
function graymarket_get_logo_url($logo_url) {
    // Get default logo URL from settings instead of hardcoding
    $default_logo = get_option('graymarket_default_logo_url', 'https://graymarketipo.com/wp-content/uploads/2025/08/Gray-Market-IPO-Logo.png');
    
    // Return default logo if no URL provided or empty
    if (empty($logo_url) || trim($logo_url) === '') {
        return $default_logo;
    }
    
    return $logo_url;
}

// Register settings
add_action('admin_init', 'graymarket_settings_init');

function graymarket_settings_init() {
    // Register setting for default logo URL
    register_setting(
        'graymarket_settings_group',
        'graymarket_default_logo_url',
        array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => 'https://graymarketipo.com/wp-content/uploads/2025/08/Gray-Market-IPO-Logo.png'
        )
    );
    
    // Add settings section
    add_settings_section(
        'graymarket_general_settings',
        'General Settings',
        'graymarket_settings_section_callback',
        'graymarket-ipo-settings'
    );
    
    // Add settings field for default logo URL
    add_settings_field(
        'graymarket_default_logo_url',
        'Default Logo URL',
        'graymarket_default_logo_url_callback',
        'graymarket-ipo-settings',
        'graymarket_general_settings'
    );
}

// Settings section callback
function graymarket_settings_section_callback() {
    echo '<p>Configure the default settings for the Grey Market IPO plugin.</p>';
}

// Default logo URL field callback
function graymarket_default_logo_url_callback() {
    $default_logo_url = get_option('graymarket_default_logo_url', 'https://graymarketipo.com/wp-content/uploads/2025/08/Gray-Market-IPO-Logo.png');
    ?>
    <input type="url" 
           name="graymarket_default_logo_url" 
           value="<?php echo esc_attr($default_logo_url); ?>" 
           class="regular-text" 
           style="width: 400px;" 
           placeholder="https://example.com/path/to/logo.png" />
    <p class="description">Enter the default logo URL to be used when no logo is provided for IPO entries.</p>
    <?php
}

// Settings page content
function graymarket_settings_page() {
    // Check user permissions
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Display admin messages
    if (isset($_GET['settings-updated'])) {
        add_settings_error(
            'graymarket_messages',
            'graymarket_message',
            'Settings Saved',
            'updated'
        );
    }
    
    settings_errors('graymarket_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form action="options.php" method="post">
            <?php
            // Output security fields for the registered setting
            settings_fields('graymarket_settings_group');
            
            // Output setting sections and their fields
            do_settings_sections('graymarket-ipo-settings');
            
            // Output save settings button
            submit_button('Save Settings');
            ?>
        </form>

        <hr>
        <h2>Export IPO Data</h2>
        <p>You can download all IPO entries as a CSV file for backup or reporting.</p>

        <label style="display:block; margin-bottom:8px;">
            <input type="checkbox" id="include_archived_csv" value="1" />
            Include Archived IPOs in export
        </label>

        <a id="graymarket-download-csv-btn"
        href="<?php echo esc_url(
            admin_url('admin.php?page=graymarket-ipo-settings&export_csv=1&_wpnonce=' . wp_create_nonce('graymarket_export_csv'))
        ); ?>"
        class="button button-secondary">
        Download CSV
        </a>

        <script>
        document.addEventListener('DOMContentLoaded', function(){
            var btn = document.getElementById('graymarket-download-csv-btn');
            var checkbox = document.getElementById('include_archived_csv');
            var baseUrl = btn.getAttribute('href');

            checkbox.addEventListener('change', function(){
                if (checkbox.checked) {
                    btn.setAttribute('href', baseUrl + '&include_archived=1');
                } else {
                    btn.setAttribute('href', baseUrl + '&include_archived=0');
                }
            });

            // Trigger once on load
            checkbox.dispatchEvent(new Event('change'));
        });
        </script>
        
        <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 8px;">
            <h3>Current Default Logo Preview</h3>
            <?php 
            $current_logo = get_option('graymarket_default_logo_url', 'https://graymarketipo.com/wp-content/uploads/2025/08/Gray-Market-IPO-Logo.png');
            if (!empty($current_logo)) : 
            ?>
                <img src="<?php echo esc_url($current_logo); ?>" 
                     alt="Default Logo Preview" 
                     style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 10px; background: white;" />
                <p><strong>Current URL:</strong> <?php echo esc_html($current_logo); ?></p>
            <?php else : ?>
                <p>No default logo URL set.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Handle CSV export action in settings page
add_action('admin_init', function() {
    if (
        isset($_GET['page'], $_GET['export_csv'], $_GET['_wpnonce']) &&
        $_GET['page'] === 'graymarket-ipo-settings' &&
        wp_verify_nonce($_GET['_wpnonce'], 'graymarket_export_csv')
    ) {
        $ipo_data = get_option('graymarket_ipo_data', array());

        // Check if archived IPOs should be included
        $include_archived = isset($_GET['include_archived']) && $_GET['include_archived'] == '1';

        if (!$include_archived) {
            // Filter out archived IPOs
            $ipo_data = array_filter($ipo_data, function($ipo) {
                return ($ipo['archived'] ?? 'no') !== 'yes';
            });
        }

        if (empty($ipo_data)) {
            wp_die('No IPO data to export.');
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=graymarket_ipo_data.csv');
        $output = fopen('php://output', 'w');
        if (empty($ipo_data)) {
            wp_die('No IPO data to export.');
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=graymarket_ipo_data.csv');
        $output = fopen('php://output', 'w');
        $headers = array(
            'Company Name', 'Logo URL', 'Status', 'Archived',
            'GMP', 'Price', 'Estimated Price', 'Gain (%)', 'Gain Class',
            'IPO Size (Cr)', 'Lot Size', 'Open Date', 'Close Date',
            'BoA Date', 'Listing Date', 'Type', 'Read More Link',
            'Allotted', 'Listed Price', 'Listed Gain (%)', 'Listed Gain Class'
        );
        fputcsv($output, $headers);
        foreach ($ipo_data as $ipo) {
            fputcsv($output, array(
                $ipo['name'] ?? '',
                $ipo['logo'] ?? '',
                $ipo['status'] ?? '',
                $ipo['archived'] ?? '',
                $ipo['gmp'] ?? '',
                $ipo['price'] ?? '',
                $ipo['estimated_price'] ?? '',
                $ipo['gain'] ?? '',
                $ipo['gain_class'] ?? '',
                $ipo['ipo_size'] ?? '',
                $ipo['lot'] ?? '',
                $ipo['open_date'] ?? '',
                $ipo['close_date'] ?? '',
                $ipo['boa_date'] ?? '',
                $ipo['listing_date'] ?? '',
                $ipo['type'] ?? '',
                $ipo['read_more_link'] ?? '',
                $ipo['allotted'] ?? '',
                $ipo['listed_price'] ?? '',
                $ipo['listed_gain'] ?? '',
                $ipo['listed_gain_class'] ?? ''
            ));
        }
        fclose($output);
        exit;
    }
});

// AJAX handler for adding new entry
add_action('wp_ajax_graymarket_add_new_entry', 'graymarket_add_new_entry');

function graymarket_add_new_entry() {
    if (!wp_verify_nonce($_POST['nonce'], 'graymarket_admin_action')) {
        wp_send_json_error('Security check failed');
    }
    
    $current_data = get_option('graymarket_ipo_data', array());
    
    // Calculate gain automatically
    $gmp = floatval($_POST['gmp']);
    $price = floatval($_POST['price']);
    $gain = 0;
    if ($price > 0) {
        $gain = round(($gmp / $price) * 100, 2);
    }
    
    // Calculate estimated price automatically (only if both GMP and Price have values)
    $estimated_price = '';
    if (!empty($_POST['gmp']) && !empty($_POST['price'])) {
        $estimated_price = $gmp + $price;
    }
    
    // Determine gain class
    $gain_class = 'neutral';
    if ($gain > 0) {
        $gain_class = 'positive';
    } elseif ($gain < 0) {
        $gain_class = 'negative';
    }
    
    // Use default logo if none provided
    $logo_url = graymarket_get_logo_url($_POST['logo']);
    
    // Format IPO Size to remove ₹ and Cr if user enters them
    $ipo_size = sanitize_text_field($_POST['ipo_size']);
    $ipo_size = str_replace(['₹', 'Cr', 'cr', 'Crores', 'crores'], '', $ipo_size);
    $ipo_size = trim($ipo_size);
    
    // Format Lot Size to remove "Shares" if user enters it
    $lot = sanitize_text_field($_POST['lot']);
    $lot = str_replace(['Shares', 'shares', 'Share', 'share'], '', $lot);
    $lot = trim($lot);
    
    // **AUTO-CALCULATE STATUS** based on dates
    $status = graymarket_calculate_status(
        sanitize_text_field($_POST['open_date']),
        sanitize_text_field($_POST['close_date']),
        sanitize_text_field($_POST['listing_date'])
    );
    
    $new_entry = array(
        'name' => sanitize_text_field($_POST['name']),
        'logo' => esc_url_raw($logo_url),
        'status' => $status,  // Auto-calculated
        'archived' => (isset($_POST['archived']) && $_POST['archived'] === 'yes') ? 'yes' : 'no',
        'gmp' => sanitize_text_field($_POST['gmp']),
        'price' => sanitize_text_field($_POST['price']),
        'estimated_price' => $estimated_price,
        'gain' => $gain,
        'gain_class' => $gain_class,
        'ipo_size' => $ipo_size,
        'lot' => $lot,
        'open_date' => sanitize_text_field($_POST['open_date']),
        'close_date' => sanitize_text_field($_POST['close_date']),
        'boa_date' => sanitize_text_field($_POST['boa_date']),
        'listing_date' => sanitize_text_field($_POST['listing_date']),
        'type' => sanitize_text_field($_POST['type']),
        'read_more_link' => esc_url_raw($_POST['read_more_link']),
        'allotted' => sanitize_text_field($_POST['allotted'] ?? 'no'),  // Added allotted field
        'listed_price' => sanitize_text_field($_POST['listed_price'] ?? ''),  // Added listed price field
    );
    
    // Calculate listed gain if status is Listed and listed price is provided
    if ($status === 'Listed' && !empty($new_entry['listed_price'])) {
        $listed_price = floatval($new_entry['listed_price']);
        if ($price > 0) {
            $listed_gain = round((($listed_price - $price) / $price) * 100, 2);
            $listed_gain_class = 'neutral';
            if ($listed_gain > 0) {
                $listed_gain_class = 'positive';
            } elseif ($listed_gain < 0) {
                $listed_gain_class = 'negative';
            }
            
            $new_entry['listed_gain'] = $listed_gain;
            $new_entry['listed_gain_class'] = $listed_gain_class;
        }
    }
    
    // Add new entry at the beginning of the array (top)
    array_unshift($current_data, $new_entry);
    
    // Apply current sorting
    $primary_sort = get_option('graymarket_primary_sort', 'status_priority');
    $secondary_sort = get_option('graymarket_secondary_sort', 'none');
    $current_data = graymarket_sort_ipo_data($current_data, $primary_sort, $secondary_sort);
    
    update_option('graymarket_ipo_data', $current_data);
    
    // Return the new entry HTML and total count
    wp_send_json_success(array(
        'message' => 'New IPO entry added successfully!',
        'entry_html' => graymarket_generate_entry_html($new_entry, 0),
        'total_entries' => count($current_data)
    ));
}

// AJAX handler for updating entry
add_action('wp_ajax_graymarket_update_entry', 'graymarket_update_entry');

function graymarket_update_entry() {
    if (!wp_verify_nonce($_POST['nonce'], 'graymarket_admin_action')) {
        wp_send_json_error('Security check failed');
    }
    
    $current_data = get_option('graymarket_ipo_data', array());
    $entry_index = intval($_POST['entry_index']);
    
    if (!isset($current_data[$entry_index])) {
        wp_send_json_error('Entry not found');
    }
    
    // Calculate gain automatically
    $gmp = floatval($_POST['gmp']);
    $price = floatval($_POST['price']);
    $gain = 0;
    if ($price > 0) {
        $gain = round(($gmp / $price) * 100, 2);
    }
    
    // Calculate estimated price automatically (only if both GMP and Price have values)
    $estimated_price = '';
    if (!empty($_POST['gmp']) && !empty($_POST['price'])) {
        $estimated_price = $gmp + $price;
    }
    
    // Determine gain class
    $gain_class = 'neutral';
    if ($gain > 0) {
        $gain_class = 'positive';
    } elseif ($gain < 0) {
        $gain_class = 'negative';
    }
    
    // Use default logo if none provided
    $logo_url = graymarket_get_logo_url($_POST['logo']);
    
    // Format IPO Size to remove ₹ and Cr if user enters them
    $ipo_size = sanitize_text_field($_POST['ipo_size']);
    $ipo_size = str_replace(['₹', 'Cr', 'cr', 'Crores', 'crores'], '', $ipo_size);
    $ipo_size = trim($ipo_size);
    
    // Format Lot Size to remove "Shares" if user enters it
    $lot = sanitize_text_field($_POST['lot']);
    $lot = str_replace(['Shares', 'shares', 'Share', 'share'], '', $lot);
    $lot = trim($lot);
    
    // **AUTO-CALCULATE STATUS** based on dates
    $status = graymarket_calculate_status(
        sanitize_text_field($_POST['open_date']),
        sanitize_text_field($_POST['close_date']),
        sanitize_text_field($_POST['listing_date'])
    );
    
    $updated_entry = array(
        'name' => sanitize_text_field($_POST['name']),
        'logo' => esc_url_raw($logo_url),
        'status' => $status,  // Auto-calculated
        'archived' => (isset($_POST['archived']) && $_POST['archived'] === 'yes') ? 'yes' : 'no',
        'gmp' => sanitize_text_field($_POST['gmp']),
        'price' => sanitize_text_field($_POST['price']),
        'estimated_price' => $estimated_price,
        'gain' => $gain,
        'gain_class' => $gain_class,
        'ipo_size' => $ipo_size,
        'lot' => $lot,
        'open_date' => sanitize_text_field($_POST['open_date']),
        'close_date' => sanitize_text_field($_POST['close_date']),
        'boa_date' => sanitize_text_field($_POST['boa_date']),
        'listing_date' => sanitize_text_field($_POST['listing_date']),
        'type' => sanitize_text_field($_POST['type']),
        'read_more_link' => esc_url_raw($_POST['read_more_link']),
        'allotted' => sanitize_text_field($_POST['allotted'] ?? 'no'),  // Added allotted field
        'listed_price' => sanitize_text_field($_POST['listed_price'] ?? ''),  // Added listed price field
    );
    
    // Calculate listed gain if status is Listed and listed price is provided
    if ($status === 'Listed' && !empty($updated_entry['listed_price'])) {
        $listed_price = floatval($updated_entry['listed_price']);
        if ($price > 0) {
            $listed_gain = round((($listed_price - $price) / $price) * 100, 2);
            $listed_gain_class = 'neutral';
            if ($listed_gain > 0) {
                $listed_gain_class = 'positive';
            } elseif ($listed_gain < 0) {
                $listed_gain_class = 'negative';
            }
            
            $updated_entry['listed_gain'] = $listed_gain;
            $updated_entry['listed_gain_class'] = $listed_gain_class;
        }
    }
    
    $current_data[$entry_index] = $updated_entry;
    
    // Apply current sorting
    $primary_sort = get_option('graymarket_primary_sort', 'status_priority');
    $secondary_sort = get_option('graymarket_secondary_sort', 'none');
    $current_data = graymarket_sort_ipo_data($current_data, $primary_sort, $secondary_sort);
    
    update_option('graymarket_ipo_data', $current_data);
    
    wp_send_json_success(array(
        'message' => 'IPO entry updated successfully!',
        'updated_entry' => $updated_entry
    ));
}

// AJAX handler for deleting entry
add_action('wp_ajax_graymarket_delete_entry', 'graymarket_delete_entry');

function graymarket_delete_entry() {
    if (!wp_verify_nonce($_POST['nonce'], 'graymarket_admin_action')) {
        wp_send_json_error('Security check failed');
    }
    
    $current_data = get_option('graymarket_ipo_data', array());
    $entry_index = intval($_POST['entry_index']);
    
    if (!isset($current_data[$entry_index])) {
        wp_send_json_error('Entry not found');
    }
    
    unset($current_data[$entry_index]);
    $current_data = array_values($current_data); // Re-index array
    update_option('graymarket_ipo_data', $current_data);
    
    wp_send_json_success(array(
        'message' => 'IPO entry deleted successfully!',
        'total_entries' => count($current_data)
    ));
}

// AJAX handler for saving sort order
add_action('wp_ajax_graymarket_save_sort_order', 'graymarket_save_sort_order');

function graymarket_save_sort_order() {
    if (!wp_verify_nonce($_POST['nonce'], 'graymarket_admin_action')) {
        wp_send_json_error('Security check failed');
    }
    
    $primary_sort = sanitize_text_field($_POST['primary_sort']);
    $secondary_sort = sanitize_text_field($_POST['secondary_sort']);
    
    update_option('graymarket_primary_sort', $primary_sort);
    update_option('graymarket_secondary_sort', $secondary_sort);
    
    wp_send_json_success('Sort order saved successfully!');
}

// Helper function to generate entry HTML
function graymarket_generate_entry_html($ipo, $index) {
    ob_start();
    ?>
    <div class="graymarket-list-item"
    data-index="<?php echo $index; ?>"
    data-name="<?php echo esc_attr(strtolower($ipo['name'] ?? '')); ?>"
    data-status="<?php echo esc_attr(strtolower(str_replace(' ', '-', $ipo['status'] ?? 'upcoming'))); ?>"
    data-type="<?php echo esc_attr(strtolower($ipo['type'] ?? 'mainboard')); ?>"
    data-archived="<?php echo esc_attr($ipo['archived'] ?? 'no'); ?>"
    data-open-date="<?php echo esc_attr(graymarket_normalize_date($ipo['open_date'] ?? '')); ?>"
    data-close-date="<?php echo esc_attr(graymarket_normalize_date($ipo['close_date'] ?? '')); ?>"
    data-boa-date="<?php echo esc_attr(graymarket_normalize_date($ipo['boa_date'] ?? '')); ?>"
    data-listing-date="<?php echo esc_attr(graymarket_normalize_date($ipo['listing_date'] ?? '')); ?>"
    style="background: white; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; padding: 20px; position: relative;">
        
        <div class="graymarket-item-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; color: #333;">
                Entry #<?php echo ($index + 1); ?> - <?php echo esc_html($ipo['name']); ?>
                <?php if (isset($ipo['archived']) && $ipo['archived'] === 'yes') : ?>
                    <span class="graymarket-archived-badge">
                        Archived
                    </span>
                <?php endif; ?>

            </h3>
            <div class="graymarket-item-actions" style="display: flex; align-items: center; gap: 10px;">
                <button class="button graymarket-edit-btn" data-index="<?php echo $index; ?>">Edit</button>
                <button class="button button-link-delete graymarket-delete-btn" data-index="<?php echo $index; ?>">Delete</button>
            </div>
        </div>
        
        <!-- Display Mode -->
        <div class="graymarket-display-mode" id="display-<?php echo $index; ?>">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div><strong>🏢 Company Name:</strong> <span class="graymarket-company-name"><?php echo esc_html($ipo['name']); ?></span></div>
                <div><strong>🖼️ Logo URL:</strong> <a href="<?php echo esc_url($ipo['logo']); ?>" target="_blank">View Logo</a></div>
                <div><strong>📊 Status:</strong> 
                    <span style="background: <?php echo $ipo['status'] == 'Upcoming' ? '#F9C10B' : ($ipo['status'] == 'Open' ? '#278754' : ($ipo['status'] == 'Closing Today' ? '#F3627A' : ($ipo['status'] == 'Close' ? '#FF4500' : '#6F42C1'))); ?>; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;"><?php echo esc_html($ipo['status'] ?? 'Upcoming'); ?></span> 
                    <?php if (($ipo['status'] ?? '') === 'Close' && !empty($ipo['allotted']) && $ipo['allotted'] === 'yes'): ?>
                        <span style="background: #17a2b8; color: white; padding: 2px 8px; border-radius: 4px; font-size: 10px; text-transform: uppercase; margin-left: 5px;">Allotted</span>
                    <?php endif; ?>
                </div>
                <div><strong>📈 IPO GMP:</strong> <?php echo empty($ipo['gmp']) || $ipo['gmp'] === '' ? 'N/A' : '₹' . esc_html($ipo['gmp']); ?></div>
                <div><strong>💰 Price:</strong> <?php echo empty($ipo['price']) || $ipo['price'] === '' ? 'N/A' : '₹' . esc_html($ipo['price']); ?></div>
                
                <?php if (($ipo['status'] ?? '') === 'Listed'): ?>
                <div><strong>💸 Listed Price:</strong> <?php echo empty($ipo['listed_price']) || $ipo['listed_price'] === '' ? 'N/A' : '₹' . esc_html($ipo['listed_price']); ?></div>
                <div><strong>📊 Profit/Loss:</strong> 
                    <span style="padding: 2px 8px; border-radius: 4px; background: <?php echo ($ipo['listed_gain_class'] ?? 'neutral') == 'positive' ? '#d4edda' : (($ipo['listed_gain_class'] ?? 'neutral') == 'negative' ? '#f8d7da' : '#e2e3e5'); ?>; color: <?php echo ($ipo['listed_gain_class'] ?? 'neutral') == 'positive' ? '#155724' : (($ipo['listed_gain_class'] ?? 'neutral') == 'negative' ? '#721c24' : '#383d41'); ?>;"><?php echo esc_html($ipo['listed_gain'] ?? '0'); ?>%</span>
                </div>
                <?php else: ?>
                <div><strong>💸 Estimated Price:</strong> <?php echo (empty($ipo['estimated_price']) || $ipo['estimated_price'] === '' || $ipo['estimated_price'] === 0) ? 'N/A' : '₹' . esc_html($ipo['estimated_price']); ?></div>
                <div><strong>📊 Profit/Loss:</strong> 
                    <span style="padding: 2px 8px; border-radius: 4px; background: <?php echo $ipo['gain_class'] == 'positive' ? '#d4edda' : ($ipo['gain_class'] == 'negative' ? '#f8d7da' : '#e2e3e5'); ?>; color: <?php echo $ipo['gain_class'] == 'positive' ? '#155724' : ($ipo['gain_class'] == 'negative' ? '#721c24' : '#383d41'); ?>;"><?php echo esc_html($ipo['gain']); ?>%</span>
                </div>
                <?php endif; ?>
                
                <div><strong>💼 IPO Size:</strong> <?php echo empty($ipo['ipo_size']) || $ipo['ipo_size'] === '' ? '₹N/A Cr' : '₹' . esc_html($ipo['ipo_size']) . ' Cr'; ?></div>
                <div><strong>📦 Lot Size:</strong> <?php echo empty($ipo['lot']) || $ipo['lot'] === '' ? 'N/A Shares' : esc_html($ipo['lot']) . ' Shares'; ?></div>
                <div><strong>📅 Open Date:</strong> <?php echo esc_html(graymarket_format_date_display($ipo['open_date'] ?? '')); ?></div>
                <div><strong>📅 Close Date:</strong> <?php echo esc_html(graymarket_format_date_display($ipo['close_date'] ?? '')); ?></div>
                <div><strong>📅 BoA Date:</strong> <?php echo esc_html(graymarket_format_date_display($ipo['boa_date'] ?? '')); ?></div>
                <div><strong>📅 Listing Date:</strong> <?php echo esc_html(graymarket_format_date_display($ipo['listing_date'] ?? '')); ?></div>
                <div><strong>🏷️ Type:</strong> <span style="background: <?php echo ($ipo['type'] ?? 'Mainboard') == 'NSE SME' ? '#007bff' : (($ipo['type'] ?? 'Mainboard') == 'BSE SME' ? '#28a745' : '#6f42c1'); ?>; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;"><?php echo esc_html($ipo['type'] ?? 'Mainboard'); ?></span></div>
                <?php if (!empty($ipo['read_more_link'])): ?>
                <div style="text-align: center; grid-column: 1 / -1;"><strong>🔗 Read More:</strong> <a href="<?php echo esc_url($ipo['read_more_link']); ?>" target="_blank">View Details</a></div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Edit Mode -->
        <div class="graymarket-edit-mode" id="edit-<?php echo $index; ?>" style="display: none;">
            <form class="graymarket-edit-form" data-index="<?php echo $index; ?>">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">🏢 Company Name</label>
                        <input type="text" name="name" value="<?php echo esc_attr($ipo['name']); ?>" class="regular-text" style="width: 100%;" required />
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">🖼️ Logo URL</label>
                        <input type="url" name="logo" value="<?php echo esc_attr($ipo['logo']); ?>" class="regular-text" style="width: 100%;" />
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">📊 Status</label>
                        <p style="background: #e7f3ff; padding: 8px; border-radius: 4px; margin: 0; font-size: 12px; color: #007cba;"><strong>Auto-calculated based on dates</strong></p>
                    </div>
                    
                    <?php if (($ipo['status'] ?? '') === 'Close'): ?>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">📋 Allotted</label>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="allotted" value="yes" <?php checked($ipo['allotted'] ?? 'no', 'yes'); ?>>
                            <span style="font-size: 14px;">Allotted</span>
                        </label>
                    </div>
                    <?php endif; ?>

                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">🗄 Archived</label>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="archived" value="yes" <?php checked($ipo['archived'] ?? 'no', 'yes'); ?>>
                            <span style="font-size: 14px;">Mark as Archived</span>
                        </label>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">📈 IPO GMP</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">₹</span>
                            <input type="number" name="gmp" value="<?php echo esc_attr($ipo['gmp']); ?>" class="regular-text gmp-input" style="width: 100%; padding-left: 25px;" step="0.01" />
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">💰 Price</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">₹</span>
                            <input type="number" name="price" value="<?php echo esc_attr($ipo['price']); ?>" class="regular-text price-input" style="width: 100%; padding-left: 25px;" step="0.01" />
                        </div>
                    </div>
                    
                    <?php if (($ipo['status'] ?? '') === 'Listed'): ?>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">💸 Listed Price</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">₹</span>
                            <input type="number" name="listed_price" value="<?php echo esc_attr($ipo['listed_price'] ?? ''); ?>" class="regular-text listed-price-input" style="width: 100%; padding-left: 25px;" step="0.01" />
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">📊 Listed Profit/Loss</label>
                        <input type="text" name="listed_gain" value="<?php echo esc_attr(($ipo['listed_gain'] ?? '0') . '%'); ?>" class="regular-text listed-gain-display" style="width: 100%; background: #f5f5f5; cursor: not-allowed;" readonly />
                    </div>
                    <?php else: ?>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">💸 Estimated Price</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">₹</span>
                            <input type="number" name="estimated_price" value="<?php echo esc_attr($ipo['estimated_price'] ?? ''); ?>" class="regular-text estimated-price-display" style="width: 100%; padding-left: 25px; background: #f5f5f5; cursor: not-allowed;" readonly />
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">📊 Profit/Loss</label>
                        <input type="text" name="gain" value="<?php echo esc_attr($ipo['gain']); ?>%" class="regular-text gain-display" style="width: 100%; background: #f5f5f5; cursor: not-allowed;" readonly />
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">💼 IPO Size</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">₹</span>
                            <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">Cr</span>
                            <input type="text" name="ipo_size" value="<?php echo esc_attr($ipo['ipo_size'] ?? ''); ?>" class="regular-text" style="width: 100%; padding-left: 25px; padding-right: 35px;" placeholder="6,400" />
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">📦 Lot Size</label>
                        <div style="position: relative;">
                            <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">Shares</span>
                            <input type="number" name="lot" value="<?php echo esc_attr($ipo['lot'] ?? ''); ?>" class="regular-text" style="width: 100%; padding-right: 60px;" placeholder="150" />
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">📅 Open Date</label>
                        <input type="date" name="open_date" value="<?php echo esc_attr($ipo['open_date'] ?? ''); ?>" class="regular-text" style="width: 100%;" />
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">📅 Close Date</label>
                        <input type="date" name="close_date" value="<?php echo esc_attr($ipo['close_date'] ?? ''); ?>" class="regular-text" style="width: 100%;" />
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">📅 BoA Date</label>
                        <input type="date" name="boa_date" value="<?php echo esc_attr($ipo['boa_date'] ?? ''); ?>" class="regular-text" style="width: 100%;" />
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">📅 Listing Date</label>
                        <input type="date" name="listing_date" value="<?php echo esc_attr($ipo['listing_date'] ?? ''); ?>" class="regular-text" style="width: 100%;" />
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">🏷️ Type</label>
                        <select name="type" style="width: 100%; height: 32px;">
                            <option value="" disabled <?php selected($ipo['type'] ?? '', ''); ?>>Select IPO Type</option>
                            <option value="Mainboard" <?php selected($ipo['type'] ?? '', 'Mainboard'); ?>>Mainboard</option>
                            <option value="NSE SME" <?php selected($ipo['type'] ?? '', 'NSE SME'); ?>>NSE SME</option>
                            <option value="BSE SME" <?php selected($ipo['type'] ?? '', 'BSE SME'); ?>>BSE SME</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">🔗 Read More Link</label>
                        <input type="url" name="read_more_link" value="<?php echo esc_attr($ipo['read_more_link'] ?? ''); ?>" class="regular-text" style="width: 100%;" placeholder="https://example.com" />
                    </div>
                    
                    <div style="display: flex; gap: 10px; grid-column: span 2;">
                        <button type="submit" class="button button-primary" style="height: 32px;">Save</button>
                        <button type="button" class="button graymarket-cancel-btn" data-index="<?php echo $index; ?>" style="height: 32px;">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Admin page content with improved list view and inline editing + Sorting Feature + Search & Filter
function graymarket_admin_page() {
    // Get current data and apply sorting
    $current_data = get_option('graymarket_ipo_data', array());
    
    // Update status for all entries automatically
    $current_data = graymarket_update_all_statuses($current_data);
    
    // Apply sorting to admin display as well
    $primary_sort = get_option('graymarket_primary_sort', 'status_priority');
    $secondary_sort = get_option('graymarket_secondary_sort', 'none');
    $current_data = graymarket_sort_ipo_data($current_data, $primary_sort, $secondary_sort);
    
    // Get current sort settings for form display
    $current_primary_sort = get_option('graymarket_primary_sort', 'status_priority');
    $current_secondary_sort = get_option('graymarket_secondary_sort', 'none');
    
    // **NEW ADDITION: Count items for each filter category**

    // Initialise filter counts to avoid undefined variable/keys
    $filter_counts = array(
        'all' => 0,
        'upcoming' => 0,
        'open' => 0,
        'closing-today' => 0,
        'close' => 0,
        'listed' => 0,
        'archived' => 0
    );

    foreach ($current_data as $ipo) {
        if (($ipo['archived'] ?? 'no') === 'yes') {
            $filter_counts['archived']++;
        } else {
            $filter_counts['all']++;
            $status = strtolower(str_replace(' ', '-', $ipo['status'] ?? 'upcoming'));
            if (isset($filter_counts[$status])) {
                $filter_counts[$status]++;
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Grey Market IPO Management</h1>
        
        <!-- Add New Entry Form -->
        <div class="graymarket-add-new-section" style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <h2>Add New IPO Entry</h2>
            <form id="graymarket-add-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">🏢 Company Name</label>
                    <input type="text" name="name" class="regular-text" style="width: 100%;" required />
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">🖼️ Logo URL</label>
                    <input type="url" name="logo" class="regular-text" style="width: 100%;" placeholder="https://example.com/logo.png" />
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">📊 Status</label>
                    <p style="background: #e7f3ff; padding: 8px; border-radius: 4px; margin: 0; font-size: 12px; color: #007cba;"><strong>Auto-calculated based on dates</strong></p>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">📋 Allotted</label>
                    <label style="display: flex; align-items: center; gap: 5px; margin-top: 8px;">
                        <input type="checkbox" name="allotted" value="yes">
                        <span style="font-size: 14px;">Allotted</span>
                    </label>
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">🗄 Archived</label>
                    <label style="display: flex; align-items: center; gap: 5px; margin-top: 8px;">
                        <input type="checkbox" name="archived" value="yes">
                        <span style="font-size: 14px;">Mark as Archived</span>
                    </label>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">📈 IPO GMP</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">₹</span>
                        <input type="number" name="gmp" class="regular-text gmp-input" style="width: 100%; padding-left: 25px;" placeholder="37" step="0.01" />
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">💰 Price</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">₹</span>
                        <input type="number" name="price" class="regular-text price-input" style="width: 100%; padding-left: 25px;" placeholder="70" step="0.01" />
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">💸 Listed Price (if listed)</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">₹</span>
                        <input type="number" name="listed_price" class="regular-text listed-price-input" style="width: 100%; padding-left: 25px;" placeholder="85" step="0.01" />
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">💸 Estimated Price</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">₹</span>
                        <input type="number" name="estimated_price" class="regular-text estimated-price-display" style="width: 100%; padding-left: 25px; background: #f5f5f5; cursor: not-allowed;" placeholder="Auto-calculated" readonly />
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">📊 Profit/Loss</label>
                    <input type="text" name="gain" class="regular-text gain-display" style="width: 100%; background: #f5f5f5; cursor: not-allowed;" placeholder="Auto-calculated" readonly />
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">💼 IPO Size</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">₹</span>
                        <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">Cr</span>
                        <input type="text" name="ipo_size" class="regular-text" style="width: 100%; padding-left: 25px; padding-right: 35px;" placeholder="6,400" />
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">📦 Lot Size</label>
                    <div style="position: relative;">
                        <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">Shares</span>
                        <input type="number" name="lot" class="regular-text" style="width: 100%; padding-right: 60px;" placeholder="150" />
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">📅 Open Date</label>
                    <input type="date" name="open_date" class="regular-text" style="width: 100%;" />
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">📅 Close Date</label>
                    <input type="date" name="close_date" class="regular-text" style="width: 100%;" />
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">📅 BoA Date</label>
                    <input type="date" name="boa_date" class="regular-text" style="width: 100%;" />
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">📅 Listing Date</label>
                    <input type="date" name="listing_date" class="regular-text" style="width: 100%;" />
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">🏷️ Type</label>
                    <select name="type" style="width: 100%; height: 32px;">
                        <option value="" disabled selected>Select IPO Type</option>
                        <option value="Mainboard">Mainboard</option>
                        <option value="NSE SME">NSE SME</option>
                        <option value="BSE SME">BSE SME</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">🔗 Read More Link</label>
                    <input type="url" name="read_more_link" class="regular-text" style="width: 100%;" placeholder="https://example.com/details" />
                </div>
                
                <div style="grid-column: span 2;">
                    <button type="submit" id="graymarket-add-btn" class="button button-primary" style="height: 32px;">Add New Entry</button>
                </div>
            </form>
        </div>
        
        <!-- Sort Order Settings Section (moved before Current IPO Entries) -->
        <div class="graymarket-sorting-section" style="background: #f0f8ff; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <h2>📊 Sort Order Settings</h2>
            <form id="graymarket-sort-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Primary Sort</label>
                    <select name="primary_sort" id="primary_sort" style="width: 100%; height: 32px;">
                        <option value="status_priority" <?php selected($current_primary_sort, 'status_priority'); ?>>Status Priority (Recommended)</option>
                        <option value="listing_date_desc" <?php selected($current_primary_sort, 'listing_date_desc'); ?>>Listing Date (Newest First)</option>
                        <option value="listing_date_asc" <?php selected($current_primary_sort, 'listing_date_asc'); ?>>Listing Date (Oldest First)</option>
                        <option value="close_date_desc" <?php selected($current_primary_sort, 'close_date_desc'); ?>>Close Date (Newest First)</option>
                        <option value="close_date_asc" <?php selected($current_primary_sort, 'close_date_asc'); ?>>Close Date (Oldest First)</option>
                        <option value="open_date_desc" <?php selected($current_primary_sort, 'open_date_desc'); ?>>Open Date (Newest First)</option>
                        <option value="open_date_asc" <?php selected($current_primary_sort, 'open_date_asc'); ?>>Open Date (Oldest First)</option>
                        <option value="name_asc" <?php selected($current_primary_sort, 'name_asc'); ?>>Company Name (A-Z)</option>
                        <option value="name_desc" <?php selected($current_primary_sort, 'name_desc'); ?>>Company Name (Z-A)</option>
                        <option value="gmp_desc" <?php selected($current_primary_sort, 'gmp_desc'); ?>>IPO GMP (High to Low)</option>
                        <option value="gmp_asc" <?php selected($current_primary_sort, 'gmp_asc'); ?>>IPO GMP (Low to High)</option>
                        <option value="manual" <?php selected($current_primary_sort, 'manual'); ?>>Manual Order</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Secondary Sort</label>
                    <select name="secondary_sort" id="secondary_sort" style="width: 100%; height: 32px;">
                        <option value="none" <?php selected($current_secondary_sort, 'none'); ?>>None</option>
                        <option value="listing_date_desc" <?php selected($current_secondary_sort, 'listing_date_desc'); ?>>Listing Date (Newest First)</option>
                        <option value="name_asc" <?php selected($current_secondary_sort, 'name_asc'); ?>>Company Name (A-Z)</option>
                        <option value="gmp_desc" <?php selected($current_secondary_sort, 'gmp_desc'); ?>>IPO GMP (High to Low)</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="button button-primary" style="height: 32px;">Apply Sort Order</button>
                    <button type="button" id="reset-sort" class="button" style="height: 32px; margin-left: 10px;">Reset to Default</button>
                </div>
            </form>
        </div>
        
        <!-- **NEW ADDITION: Search and Filter Section for Admin** -->
        <div class="graymarket-search-filter" style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e1e5e9; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <input type="text" 
                   id="graymarket-admin-search" 
                   class="graymarket-search-bar" 
                   placeholder="Search IPO by Company Name... 🔍" 
                   style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; margin-bottom: 15px; box-sizing: border-box;" />
            
            <!-- Filter Buttons with counts -->
            <div class="graymarket-filter-buttons" style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                <button class="graymarket-admin-filter-btn active" data-filter="all">
                All <span class="graymarket-filter-count"><?php echo $filter_counts['all']; ?></span>
                </button>
                <button class="graymarket-admin-filter-btn upcoming" data-filter="upcoming">
                Upcoming <span class="graymarket-filter-count"><?php echo $filter_counts['upcoming']; ?></span>
                </button>
                <button class="graymarket-admin-filter-btn open" data-filter="open">
                Open <span class="graymarket-filter-count"><?php echo $filter_counts['open']; ?></span>
                </button>
                <button class="graymarket-admin-filter-btn closing-today" data-filter="closing-today">
                Closing Today <span class="graymarket-filter-count"><?php echo $filter_counts['closing-today']; ?></span>
                </button>
                <button class="graymarket-admin-filter-btn close" data-filter="close">
                Close <span class="graymarket-filter-count"><?php echo $filter_counts['close']; ?></span>
                </button>
                <button class="graymarket-admin-filter-btn listed" data-filter="listed">
                Listed <span class="graymarket-filter-count"><?php echo $filter_counts['listed']; ?></span>
                </button>
                <button class="graymarket-admin-filter-btn archived" data-filter="archived">
                    Archived <span class="graymarket-filter-count"><?php echo $filter_counts['archived']; ?></span>
                </button>
            </div>

            <!-- NEW: Type Filter Checkboxes for Admin -->
            <div class="graymarket-type-filter" style="display: flex; gap: 15px; align-items: center; justify-content: center; flex-wrap: wrap; margin-top: 15px;">
                <span class="graymarket-type-filter-title" style="font-weight: 600; color: #333; margin-right: 10px;">Type Filter:</span>
                <label style="display: flex; align-items: center; gap: 5px; font-weight: 500; cursor: pointer; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; background: white; transition: all 0.2s ease;">
                    <input type="checkbox" id="admin-mainboard-filter" checked style="margin: 0; cursor: pointer;">
                    <span>Mainboard</span>
                </label>
                <label style="display: flex; align-items: center; gap: 5px; font-weight: 500; cursor: pointer; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; background: white; transition: all 0.2s ease;">
                    <input type="checkbox" id="admin-sme-filter" checked style="margin: 0; cursor: pointer;">
                    <span>SME</span>
                </label>
            </div>

            <!-- NEW: Collapsible Date Filters -->
            <details open style="margin-top: 15px;">
                <summary style="cursor: pointer; font-weight: 600; font-size: 14px;">
                    📅 Date Filters
                </summary>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">
                    <div>
                        <label>Open Date</label>
                        <input type="date" id="admin-open-date" style="width: 100%;" />
                    </div>
                    <div>
                        <label>Close Date</label>
                        <input type="date" id="admin-close-date" style="width: 100%;" />
                    </div>
                    <div>
                        <label>BoA Date</label>
                        <input type="date" id="admin-boa-date" style="width: 100%;" />
                    </div>
                    <div>
                        <label>Listing Date</label>
                        <input type="date" id="admin-listing-date" style="width: 100%;" />
                    </div>
                </div>
            </details>
        </div>
        
        <!-- Current Entries List -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Current IPO Entries</h2>
        </div>
        
        <!-- AJAX Status Messages -->
        <div id="graymarket-ajax-message" style="display: none;"></div>
        
        <?php if (empty($current_data)): ?>
            <div class="notice notice-info" id="graymarket-no-entries">
                <p>No IPO entries found. Add your first entry using the form above.</p>
            </div>
        <?php else: ?>
            <div class="graymarket-entries-list" id="graymarket-entries-list">
                <?php foreach ($current_data as $index => $ipo): ?>
                    <?php echo graymarket_generate_entry_html($ipo, $index); ?>
                <?php endforeach; ?>
            </div>
            
            <!-- No Results Message for Admin -->
            <div class="graymarket-no-results" id="graymarket-admin-no-results" style="display: none; text-align: center; padding: 40px 20px; background: white; border-radius: 8px; border: 1px solid #e1e5e9; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px;">
                <p>No IPOs found matching your search criteria.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Add CSS for admin filter styling -->
    <style>
    /* Admin Filter Button Styles */

    /* Base Styles for Admin Filter Buttons */
    .graymarket-admin-filter-btn {
        padding: 8px 16px;
        border: 1px solid #3498db;
        background: white;
        color: black;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    /* Hover State */
    .graymarket-admin-filter-btn:hover {
        background: black !important;
        color: white !important;
        border-color: #ddd !important;
    }

    /* Active State (Default Blue) */
    .graymarket-admin-filter-btn.active {
        background: #3498db !important;
        color: white !important;
        border-color: #3498db !important;
    }

    /* Active State Per IPO Status */
    .graymarket-admin-filter-btn.upcoming.active { background: #F9C10B !important; border-color: #F9C10B !important; }
    .graymarket-admin-filter-btn.open.active { background: #278754 !important; border-color: #278754 !important; }
    .graymarket-admin-filter-btn.closing-today.active { background: #F3627A !important; border-color: #F3627A !important; }
    .graymarket-admin-filter-btn.close.active { background: #FF4500 !important; border-color: #FF4500 !important; }
    .graymarket-admin-filter-btn.listed.active { background: #6F42C1 !important; border-color: #6F42C1 !important; }

    /* Count Badge Styles */
    .graymarket-filter-count {
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        margin-left: 5px;
        background-color: #3498db;
        color: white;
    }

    .graymarket-admin-filter-btn.active .graymarket-filter-count {
        background: rgba(255,255,255,0.3) !important;
        color: white;
    }

    .graymarket-archived-badge {
        background: #6c757d;
        color: #fff;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 11px;
        margin-left: 8px;
    }

    /* Mobile Responsive Adjustments */
    @media (max-width: 768px) {
        .graymarket-admin-filter-btn {
            font-size: 12px;
            padding: 6px 12px;
        }
    }

    .graymarket-type-filter label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-weight: 500;
        cursor: pointer;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: white;
        transition: all 0.2s ease;
    }

    .graymarket-type-filter label:hover {
        background: #f8f9fa !important;
        border-color: #3498db !important;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {

        // Force Archived checkbox to be unchecked on page load
        $('#graymarket-add-form input[name="archived"]').prop('checked', false);
        
        // **FIXED: Admin Search and Filter functionality**
        const adminSearchInput = $('#graymarket-admin-search');
        const adminFilterButtons = $('.graymarket-admin-filter-btn');
        const adminEntryItems = $('.graymarket-list-item');
        const adminContainer = $('#graymarket-entries-list');
        const adminNoResults = $('#graymarket-admin-no-results');
        
        let currentAdminFilter = 'all';
        let currentAdminSearch = '';
        
        // Admin Search functionality
        if (adminSearchInput.length) {
            adminSearchInput.on('input', function() {
                currentAdminSearch = $(this).val().toLowerCase().trim();
                filterAdminItems();
            });
        }

        // NEW: Type filter checkboxes for admin
        const adminMainboardFilter = $('#admin-mainboard-filter');
        const adminSmeFilter = $('#admin-sme-filter');

        // NEW: Date filter inputs for admin
        const adminOpenDate = $('#admin-open-date');
        const adminCloseDate = $('#admin-close-date');
        const adminBoADate = $('#admin-boa-date');
        const adminListingDate = $('#admin-listing-date');

        // Trigger filtering when a date changes
        adminOpenDate.on('change', filterAdminItems);
        adminCloseDate.on('change', filterAdminItems);
        adminBoADate.on('change', filterAdminItems);
        adminListingDate.on('change', filterAdminItems);

        // NEW: Type filter functionality for admin
        if (adminMainboardFilter.length && adminSmeFilter.length) {
            adminMainboardFilter.on('change', filterAdminItems);
            adminSmeFilter.on('change', filterAdminItems);
        }
        
        // **FIXED: Admin Filter functionality**
        adminFilterButtons.each(function() {
            $(this).on('click', function() {
                // Remove active class from all buttons
                adminFilterButtons.removeClass('active');
                
                // Add active class to clicked button
                $(this).addClass('active');
                
                currentAdminFilter = $(this).data('filter');
                filterAdminItems();
            });
        });
        
        function filterAdminItems() {
            let visibleCount = 0;

            adminEntryItems.each(function () {
                const $item = $(this);

                // Read directly from data attributes (set in PHP)
                const name = ($item.data('name') || '').toString().toLowerCase();
                const status = ($item.data('status') || '').toString().trim();
                const type = ($item.data('type') || 'mainboard').toString().trim().toLowerCase();
                const archived = ($item.data('archived') || 'no').toString();

                // Dates in yyyy-mm-dd format
                const itemOpenDate = ($item.data('open-date') || '').toString();
                const itemCloseDate = ($item.data('close-date') || '').toString();
                const itemBoaDate = ($item.data('boa-date') || '').toString();
                const itemListingDate = ($item.data('listing-date') || '').toString();

                // --- SEARCH MATCH ---
                const searchMatch =
                    currentAdminSearch === '' || name.includes(currentAdminSearch);

                // --- STATUS FILTER ---
                let statusMatch = false;
                if (currentAdminFilter === 'archived') {
                    statusMatch = archived === 'yes';
                } else if (currentAdminFilter === 'all') {
                    statusMatch = archived !== 'yes';
                } else {
                    statusMatch = status === currentAdminFilter && archived !== 'yes';
                }

                // --- TYPE FILTER ---
                let typeMatch = false;
                const isMainboardChecked = adminMainboardFilter.length
                    ? adminMainboardFilter.is(':checked')
                    : true;
                const isSmeChecked = adminSmeFilter.length
                    ? adminSmeFilter.is(':checked')
                    : true;

                if (isMainboardChecked && isSmeChecked) {
                    typeMatch = true;
                } else if (isMainboardChecked && !isSmeChecked) {
                    typeMatch = type === 'mainboard';
                } else if (!isMainboardChecked && isSmeChecked) {
                    typeMatch =
                        type === 'nse sme' ||
                        type === 'nse-sme' ||
                        type === 'bse sme' ||
                        type === 'bse-sme';
                } else {
                    typeMatch = false;
                }

                // --- DATE FILTER ---
                let dateMatch = true;

                const openDateVal = adminOpenDate.val();
                const closeDateVal = adminCloseDate.val();
                const boaDateVal = adminBoADate.val();
                const listingDateVal = adminListingDate.val();

                if (openDateVal) dateMatch = dateMatch && itemOpenDate === openDateVal;
                if (closeDateVal) dateMatch = dateMatch && itemCloseDate === closeDateVal;
                if (boaDateVal) dateMatch = dateMatch && itemBoaDate === boaDateVal;
                if (listingDateVal)
                    dateMatch = dateMatch && itemListingDate === listingDateVal;

                // --- FINAL SHOW/HIDE ---
                if (searchMatch && statusMatch && typeMatch && dateMatch) {
                    $item.show();
                    visibleCount++;
                } else {
                    $item.hide();
                }
            });

            // Renumber entries for visible ones
            let newNum = 1;
            adminEntryItems.filter(':visible').each(function () {
                const $header = $(this).find('.graymarket-item-header h3');
                const html = $header.html();
                const dashPos = html.indexOf(' - ');
                if (dashPos !== -1) {
                    let suffix = html.substring(dashPos + 3);
                    $header.html('Entry #' + newNum++ + ' - ' + suffix);
                } else {
                    $header.html('Entry #' + newNum++ + ' - ' + html);
                }
            });

            // Update header count
            const $headerTitle = $('h2:contains("Current IPO Entries")');
            if ($headerTitle.length) {
                $headerTitle.text(`Current IPO Entries – ${visibleCount} Entries`);
            }

            // Show/hide "no results" message
            if (visibleCount === 0) {
                adminContainer.hide();
                adminNoResults.show();
            } else {
                adminContainer.show();
                adminNoResults.hide();
            }
        }
        
        // Function to calculate gain and estimated price
        function calculateValues($form) {
            var gmp = parseFloat($form.find('.gmp-input').val()) || 0;
            var price = parseFloat($form.find('.price-input').val()) || 0;
            var listedPrice = parseFloat($form.find('.listed-price-input').val()) || 0;
            var gain = 0;
            var estimatedPrice = '';
            var listedGain = 0;
            
            if (price > 0) {
                gain = Math.round((gmp / price) * 100 * 100) / 100; // Round to 2 decimal places
                
                // Only calculate estimated price if both GMP and Price have values
                if (gmp > 0 || gmp === 0) { // Allow zero GMP
                    estimatedPrice = gmp + price;
                    $form.find('.estimated-price-display').val(estimatedPrice);
                }
                
                // Calculate listed gain if listed price is provided
                if (listedPrice > 0) {
                    listedGain = Math.round(((listedPrice - price) / price) * 100 * 100) / 100;
                    $form.find('.listed-gain-display').val(listedGain + '%');
                }
            } else {
                // If no price, clear estimated price
                $form.find('.estimated-price-display').val('');
            }
            
            $form.find('.gain-display').val(gain + '%');
            
            return { gain: gain, estimatedPrice: estimatedPrice, listedGain: listedGain };
        }
        
        // Auto-calculate gain and estimated price when GMP, Price, or Listed Price changes
        $(document).on('input', '.gmp-input, .price-input, .listed-price-input', function() {
            var $form = $(this).closest('form');
            calculateValues($form);
        });
        
        // Add sort form handling
        $('#graymarket-sort-form').submit(function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'graymarket_save_sort_order',
                primary_sort: $('#primary_sort').val(),
                secondary_sort: $('#secondary_sort').val(),
                nonce: '<?php echo wp_create_nonce('graymarket_admin_action'); ?>'
            };
            
            $.post(ajaxurl, formData, function(response) {
                if (response.success) {
                    showMessage(response.data, 'success');
                    // Reload page to show new sort order
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage('Error saving sort order: ' + response.data, 'error');
                }
            });
        });
        
        // Reset to default sort
        $('#reset-sort').click(function() {
            $('#primary_sort').val('status_priority');
            $('#secondary_sort').val('none');
            $('#graymarket-sort-form').submit();
        });
        
        // AJAX form submission for adding new entry
        $('#graymarket-add-form').submit(function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'graymarket_add_new_entry',
                name: $('input[name="name"]').val(),
                logo: $('input[name="logo"]').val(),
                gmp: $('input[name="gmp"]').val(),
                price: $('input[name="price"]').val(),
                listed_price: $('input[name="listed_price"]').val(),
                ipo_size: $('input[name="ipo_size"]').val(),
                lot: $('input[name="lot"]').val(),
                open_date: $('input[name="open_date"]').val(),
                close_date: $('input[name="close_date"]').val(),
                boa_date: $('input[name="boa_date"]').val(),
                listing_date: $('input[name="listing_date"]').val(),
                type: $('select[name="type"]').val(),
                read_more_link: $('input[name="read_more_link"]').val(),
                allotted: $('input[name="allotted"]:checked').val() || 'no',
                archived: $('#graymarket-add-form input[name="archived"]').is(':checked') ? 'yes' : 'no',
                nonce: '<?php echo wp_create_nonce('graymarket_admin_action'); ?>'
            };
            
            var $addBtn = $('#graymarket-add-btn');
            $addBtn.prop('disabled', true).text('Adding...');
            
            $.post(ajaxurl, formData, function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    
                    // Hide "no entries" message if exists
                    $('#graymarket-no-entries').hide();
                    
                    // If entries list doesn't exist, create it
                    if ($('#graymarket-entries-list').length === 0) {
                        $('<div class="graymarket-entries-list" id="graymarket-entries-list"></div>').insertAfter('#graymarket-ajax-message');
                    }
                    
                    // Add new entry at the top with animation
                    var $newEntry = $(response.data.entry_html);
                    $newEntry.hide().prependTo('#graymarket-entries-list').slideDown(300);
                    
                    // Update all entry numbers
                    updateAllEntryNumbers();
                    
                    // Clear form
                    $('#graymarket-add-form')[0].reset();
                    $('#graymarket-add-form input[name="archived"]').prop('checked', false);
                    
                    // Re-attach event handlers for the new entry
                    attachEntryHandlers();
                    
                    // Refresh admin filter after adding new entry
                    location.reload();
                    
                } else {
                    showMessage('Error adding entry: ' + response.data, 'error');
                }
            }).fail(function() {
                showMessage('AJAX request failed. Please try again.', 'error');
            }).always(function() {
                $addBtn.prop('disabled', false).text('Add New Entry');
            });
        });
        
        // AJAX form submission for updating entry – UPDATED for live DOM updates
        $(document).on('submit', '.graymarket-edit-form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var entryIndex = $form.data('index');
            var formData = {
                action: 'graymarket_update_entry',
                entry_index: entryIndex,
                name: $form.find('input[name="name"]').val(),
                logo: $form.find('input[name="logo"]').val(),
                gmp: $form.find('input[name="gmp"]').val(),
                price: $form.find('input[name="price"]').val(),
                listed_price: $form.find('input[name="listed_price"]').val(),
                ipo_size: $form.find('input[name="ipo_size"]').val(),
                lot: $form.find('input[name="lot"]').val(),
                open_date: $form.find('input[name="open_date"]').val(),
                close_date: $form.find('input[name="close_date"]').val(),
                boa_date: $form.find('input[name="boa_date"]').val(),
                listing_date: $form.find('input[name="listing_date"]').val(),
                type: $form.find('select[name="type"]').val(),
                read_more_link: $form.find('input[name="read_more_link"]').val(),
                allotted: $form.find('input[name="allotted"]:checked').val() || 'no',
                archived: $form.find('input[name="archived"]').is(':checked') ? 'yes' : 'no',
                nonce: '<?php echo wp_create_nonce('graymarket_admin_action'); ?>'
            };

            var $saveBtn = $form.find('button[type="submit"]');
            $saveBtn.prop('disabled', true).text('Saving...');

            $.post(ajaxurl, formData, function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');

                    var updated = response.data.updated_entry;
                    var $listItem = $form.closest('.graymarket-list-item');

                    // Update data attributes
                    $listItem.attr('data-name', (updated.name || '').toLowerCase());
                    $listItem.attr('data-status', (updated.status || 'upcoming').toLowerCase().replace(/\s+/g, '-'));
                    $listItem.attr('data-type', (updated.type || 'mainboard').toLowerCase());
                    $listItem.attr('data-archived', updated.archived === 'yes' ? 'yes' : 'no');
                    $listItem.attr('data-open-date', normalizeDate(updated.open_date));
                    $listItem.attr('data-close-date', normalizeDate(updated.close_date));
                    $listItem.attr('data-boa-date', normalizeDate(updated.boa_date));
                    $listItem.attr('data-listing-date', normalizeDate(updated.listing_date));

                    // Update header
                    var $header = $listItem.find('.graymarket-item-header h3');
                    var entryNumber = $header.text().split(' - ')[0];
                    
                    // Remove existing archived badge
                    $header.find('.graymarket-archived-badge').remove();
                    
                    // Update header text
                    var headerText = entryNumber + ' - ' + (updated.name || '');
                    if (updated.archived === 'yes') {
                        headerText += ' <span class="graymarket-archived-badge">Archived</span>';
                    }
                    $header.html(headerText);

                    // Update display mode content
                    var $display = $('#display-' + entryIndex);
                    
                    // Company Name
                    $display.find('.graymarket-company-name').text(updated.name || '');
                    
                    // Logo Link
                    $display.find('a[href]').first().attr('href', updated.logo || '');
                    
                    // Status with proper styling
                    var $statusDiv = $display.find('strong:contains("Status:")').parent();
                    var statusHtml = '<strong>📊 Status:</strong> <span>';
                    statusHtml += '<span class="graymarket-status ' + (updated.status || '').toLowerCase().replace(/\s+/g, '-') + '" style="background: ' + getStatusBg(updated.status) + '; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">' + (updated.status || 'Upcoming') + '</span>';
                    
                    // Add allotted badge if needed
                    if (updated.status === 'Close' && updated.allotted === 'yes') {
                        statusHtml += ' <span class="graymarket-allotted" style="background: #17a2b8; color: white; padding: 2px 8px; border-radius: 4px; font-size: 10px; text-transform: uppercase; margin-left: 5px;">Allotted</span>';
                    }
                    statusHtml += '</span>';
                    $statusDiv.html(statusHtml);

                    // GMP
                    $display.find('strong:contains("IPO GMP:")').parent().html(
                        '<strong>📈 IPO GMP:</strong> <span class="graymarket-gmp">' + 
                        (updated.gmp && updated.gmp !== '' ? '₹' + updated.gmp : 'N/A') + 
                        '</span>'
                    );
                    
                    // Price
                    $display.find('strong:contains("Price:")').parent().html(
                        '<strong>💰 Price:</strong> <span class="graymarket-price">' + 
                        (updated.price && updated.price !== '' ? '₹' + updated.price : 'N/A') + 
                        '</span>'
                    );

                    // Handle Listed vs Estimated Price and Profit/Loss
                    var $estimatedDiv = $display.find('strong:contains("Estimated Price:")').parent();
                    var $listedDiv = $display.find('strong:contains("Listed Price:")').parent();
                    var $profitDiv = $display.find('strong:contains("Profit/Loss:")').parent();

                    if (updated.status === 'Listed') {
                        // Show Listed Price, hide Estimated Price
                        if ($listedDiv.length === 0) {
                            $estimatedDiv.after('<div><strong>💸 Listed Price:</strong> <span class="graymarket-listed-price"></span></div>');
                            $listedDiv = $display.find('strong:contains("Listed Price:")').parent();
                        }
                        
                        $listedDiv.html(
                            '<strong>💸 Listed Price:</strong> <span class="graymarket-listed-price">' +
                            (updated.listed_price && updated.listed_price !== '' ? '₹' + updated.listed_price : 'N/A') +
                            '</span>'
                        ).show();
                        
                        $estimatedDiv.hide();
                        
                        // Update profit/loss with listed gain
                        var gainClass = updated.listed_gain_class || 'neutral';
                        var gainBg = gainClass === 'positive' ? '#d4edda' : (gainClass === 'negative' ? '#f8d7da' : '#e2e3e5');
                        var gainColor = gainClass === 'positive' ? '#155724' : (gainClass === 'negative' ? '#721c24' : '#383d41');
                        
                        $profitDiv.html(
                            '<strong>📊 Profit/Loss:</strong> <span class="graymarket-gain ' + gainClass + '" style="padding: 2px 8px; border-radius: 4px; background: ' + gainBg + '; color: ' + gainColor + ';">' + 
                            (updated.listed_gain || '0') + '%</span>'
                        );
                        
                    } else {
                        // Show Estimated Price, hide Listed Price
                        $estimatedDiv.html(
                            '<strong>💸 Estimated Price:</strong> <span class="graymarket-estimated-price">' +
                            (updated.estimated_price && updated.estimated_price !== '' && updated.estimated_price !== 0 ? '₹' + updated.estimated_price : 'N/A') +
                            '</span>'
                        ).show();
                        
                        $listedDiv.hide();
                        
                        // Update profit/loss with regular gain
                        var gainClass = updated.gain_class || 'neutral';
                        var gainBg = gainClass === 'positive' ? '#d4edda' : (gainClass === 'negative' ? '#f8d7da' : '#e2e3e5');
                        var gainColor = gainClass === 'positive' ? '#155724' : (gainClass === 'negative' ? '#721c24' : '#383d41');
                        
                        $profitDiv.html(
                            '<strong>📊 Profit/Loss:</strong> <span class="graymarket-gain ' + gainClass + '" style="padding: 2px 8px; border-radius: 4px; background: ' + gainBg + '; color: ' + gainColor + ';">' + 
                            (updated.gain || '0') + '%</span>'
                        );
                    }

                    // IPO Size
                    $display.find('strong:contains("IPO Size:")').parent().html(
                        '<strong>💼 IPO Size:</strong> <span>' + 
                        (updated.ipo_size && updated.ipo_size !== '' ? '₹' + updated.ipo_size + ' Cr' : '₹N/A Cr') + 
                        '</span>'
                    );
                    
                    // Lot Size
                    $display.find('strong:contains("Lot Size:")').parent().html(
                        '<strong>📦 Lot Size:</strong> <span>' + 
                        (updated.lot && updated.lot !== '' ? updated.lot + ' Shares' : 'N/A Shares') + 
                        '</span>'
                    );
                    
                    // Dates - Format them properly
                    $display.find('strong:contains("Open Date:")').parent().html(
                        '<strong>📅 Open Date:</strong> <span>' + formatDateForDisplay(updated.open_date || '') + '</span>'
                    );
                    
                    $display.find('strong:contains("Close Date:")').parent().html(
                        '<strong>📅 Close Date:</strong> <span>' + formatDateForDisplay(updated.close_date || '') + '</span>'
                    );
                    
                    $display.find('strong:contains("BoA Date:")').parent().html(
                        '<strong>📅 BoA Date:</strong> <span>' + formatDateForDisplay(updated.boa_date || '') + '</span>'
                    );
                    
                    $display.find('strong:contains("Listing Date:")').parent().html(
                        '<strong>📅 Listing Date:</strong> <span>' + formatDateForDisplay(updated.listing_date || '') + '</span>'
                    );
                    
                    // Type with proper styling
                    var typeBg = getTypeBg(updated.type);
                    $display.find('strong:contains("Type:")').parent().html(
                        '<strong>🏷️ Type:</strong> <span class="graymarket-type graymarket-type-' + (updated.type || 'mainboard').toLowerCase().replace(/\s+/g, '-') + '" data-type="' + (updated.type || 'Mainboard') + '" style="background: ' + typeBg + '; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">' + 
                        (updated.type || 'Mainboard') + 
                        '</span>'
                    );
                    
                    // Read More Link
                    var $readMoreDiv = $display.find('strong:contains("Read More:")').parent();
                    if (updated.read_more_link && updated.read_more_link !== '') {
                        if ($readMoreDiv.length === 0) {
                            $display.find('div:last').after('<div style="text-align: center; grid-column: 1 / -1;"><strong>🔗 Read More:</strong> <a href="" target="_blank">View Details</a></div>');
                            $readMoreDiv = $display.find('strong:contains("Read More:")').parent();
                        }
                        $readMoreDiv.find('a').attr('href', updated.read_more_link).show();
                        $readMoreDiv.show();
                    } else {
                        $readMoreDiv.hide();
                    }

                    // Switch back to display mode
                    $('#edit-' + entryIndex).hide();
                    $('#display-' + entryIndex).show();
                    $form.closest('.graymarket-list-item').find('.graymarket-edit-btn').show();

                } else {
                    showMessage('Error updating entry: ' + response.data, 'error');
                }
            }).fail(function() {
                showMessage('AJAX request failed. Please try again.', 'error');
            }).always(function() {
                $saveBtn.prop('disabled', false).text('Save');
            });
        });

        // Helper functions - ADD THESE AFTER THE ABOVE CODE
        function getStatusBg(status) {
            switch (status) {
                case 'Upcoming': return '#F9C10B';
                case 'Open': return '#278754';
                case 'Closing Today': return '#F3627A';
                case 'Close': return '#FF4500';
                case 'Listed': return '#6F42C1';
                default: return '#6F42C1';
            }
        }

        function getTypeBg(type) {
            if (type === 'NSE SME') return '#007bff';
            if (type === 'BSE SME') return '#28a745';
            return '#6f42c1';
        }

        function formatDateForDisplay(date) {
            if (!date || date === 'N/A' || date === '') {
                return 'N/A';
            }
            
            // If already in readable format, return as is
            if (date.includes('-') && !date.match(/^\d{4}-\d{2}-\d{2}$/)) {
                return date;
            }
            
            // Convert YYYY-MM-DD to DD-Month-YYYY
            if (date.match(/^\d{4}-\d{2}-\d{2}$/)) {
                var parts = date.split('-');
                var year = parts[0];
                var month = parts[1];
                var day = parts[2];
                
                var months = ['January', 'February', 'March', 'April', 'May', 'June', 
                            'July', 'August', 'September', 'October', 'November', 'December'];
                
                var monthName = months[parseInt(month) - 1];
                return day + '-' + monthName + '-' + year;
            }
            
            return date;
        }

        // Helper: Normalize displayed date (e.g., 12-August-2025) to yyyy-mm-dd
        function normalizeDate(displayDate) {
            if (!displayDate || displayDate.toLowerCase() === 'n/a') return '';
            const months = {
                january: '01', february: '02', march: '03',
                april: '04', may: '05', june: '06',
                july: '07', august: '08', september: '09',
                october: '10', november: '11', december: '12'
            };
            let parts = displayDate.split('-');
            if (parts.length === 3) {
                let day = parts[0].padStart(2, '0');
                let month = months[parts[1].toLowerCase()] || '01';
                let year = parts[2];
                return `${year}-${month}-${day}`;
            }
            return displayDate;
        }
        
        // AJAX delete entry
        $(document).on('click', '.graymarket-delete-btn', function() {
            if (!confirm('Are you sure you want to delete this entry?')) {
                return;
            }
            
            var entryIndex = $(this).data('index');
            var $entryItem = $(this).closest('.graymarket-list-item');
            
            var deleteData = {
                action: 'graymarket_delete_entry',
                entry_index: entryIndex,
                nonce: '<?php echo wp_create_nonce('graymarket_admin_action'); ?>'
            };
            
            var $deleteBtn = $(this);
            $deleteBtn.prop('disabled', true).text('Deleting...');
            
            $.post(ajaxurl, deleteData, function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    
                    // Remove entry with animation
                    $entryItem.slideUp(300, function() {
                        $(this).remove();
                        
                        // Check if no entries left
                        if (response.data.total_entries === 0) {
                            $('#graymarket-entries-list').remove();
                            $('<div class="notice notice-info" id="graymarket-no-entries"><p>No IPO entries found. Add your first entry using the form above.</p></div>').insertAfter('#graymarket-ajax-message').show();
                        } else {
                            // Update all entry numbers
                            updateAllEntryNumbers();
                        }
                    });
                    
                } else {
                    showMessage('Error deleting entry: ' + response.data, 'error');
                    $deleteBtn.prop('disabled', false).text('Delete');
                }
            }).fail(function() {
                showMessage('AJAX request failed. Please try again.', 'error');
                $deleteBtn.prop('disabled', false).text('Delete');
            });
        });
        
        // Attach event handlers for entries
        function attachEntryHandlers() {
            // Edit button click
            $('.graymarket-edit-btn').off('click').on('click', function() {
                var index = $(this).data('index');
                $('#display-' + index).hide();
                $('#edit-' + index).show();
                $(this).hide();
            });
            
            // Cancel button click
            $('.graymarket-cancel-btn').off('click').on('click', function() {
                var index = $(this).data('index');
                $('#edit-' + index).hide();
                $('#display-' + index).show();
                $('.graymarket-edit-btn[data-index="' + index + '"]').show();
            });
        }
        
        // Initial attachment of handlers
        attachEntryHandlers();
        
        // Update all entry numbers after adding new entry
        function updateAllEntryNumbers() {
            $('#graymarket-entries-list .graymarket-list-item').each(function(index) {
                var $item = $(this);
                var newNumber = index + 1;
                var $header = $item.find('.graymarket-item-header h3');
                var companyName = $header.text().split(' - ')[1] || '';
                $header.text('Entry #' + newNumber + ' - ' + companyName);
                
                // Update data-index and form field values
                $item.attr('data-index', index);
                $item.find('.graymarket-edit-btn').attr('data-index', index);
                $item.find('.graymarket-cancel-btn').attr('data-index', index);
                $item.find('.graymarket-delete-btn').attr('data-index', index);
                $item.find('.graymarket-edit-form').attr('data-index', index);
                $item.find('#display-' + $item.data('original-index')).attr('id', 'display-' + index);
                $item.find('#edit-' + $item.data('original-index')).attr('id', 'edit-' + index);
                
                // Store original index for reference
                if (!$item.data('original-index')) {
                    $item.data('original-index', index);
                }
            });
        }
        
        // Show AJAX messages
        function showMessage(message, type) {
            var className = 'notice notice-' + type;
            var $messageDiv = $('#graymarket-ajax-message');
            $messageDiv.removeClass().addClass(className).html('<p>' + message + '</p>').show();
            
            setTimeout(function() {
                $messageDiv.fadeOut();
            }, 5000);
        }

        // Helper: Normalize displayed date (e.g., 12-August-2025) to yyyy-mm-dd
        function normalizeDate(displayDate) {
            if (!displayDate || displayDate.toLowerCase() === 'n/a') return '';
            const months = {
                january: '01', february: '02', march: '03',
                april: '04', may: '05', june: '06',
                july: '07', august: '08', september: '09',
                october: '10', november: '11', december: '12'
            };
            let parts = displayDate.split('-');
            if (parts.length === 3) {
                let day = parts[0].padStart(2, '0');
                let month = months[parts[1].toLowerCase()] || '01';
                let year = parts[2];
                return `${year}-${month}-${day}`;
            }
            return displayDate;
        }
        // Run once on admin page load to apply default filter (exclude archived from "All")
        filterAdminItems();
    });
    </script>
    <?php
}
