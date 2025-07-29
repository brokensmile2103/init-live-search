<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get merged predefined synonyms based on selected dictionaries
 *
 * @return array Merged synonym map from all enabled dictionaries
 */
function init_plugin_suite_live_search_get_predefined_synonyms() {
    $selected_dictionaries = get_option(INIT_PLUGIN_SUITE_LS_PREDEFINED_DICT_OPTION, []);
    
    if (empty($selected_dictionaries) || !is_array($selected_dictionaries)) {
        return [];
    }

    $merged_synonyms = [];

    foreach ($selected_dictionaries as $dict_name) {
        $dict_synonyms = init_plugin_suite_live_search_get_dictionary_synonyms($dict_name);
        if (!empty($dict_synonyms)) {
            $merged_synonyms = array_merge_recursive($merged_synonyms, $dict_synonyms);
        }
    }

    // Remove duplicates from merged arrays
    foreach ($merged_synonyms as $key => $values) {
        if (is_array($values)) {
            $merged_synonyms[$key] = array_unique($values);
        }
    }

    return $merged_synonyms;
}

/**
 * Get synonyms for a specific dictionary
 *
 * @param string $dictionary_name The name of the dictionary
 * @return array Synonym map for the specified dictionary
 */
function init_plugin_suite_live_search_get_dictionary_synonyms($dictionary_name) {
    $dictionaries = init_plugin_suite_live_search_get_all_dictionaries();
    return isset($dictionaries[$dictionary_name]) ? $dictionaries[$dictionary_name] : [];
}

/**
 * Get all available predefined dictionaries
 *
 * @return array All predefined synonym dictionaries
 */
function init_plugin_suite_live_search_get_all_dictionaries() {
    $dictionaries = [
        'ecommerce' => init_plugin_suite_live_search_dict_ecommerce(),
        'technology' => init_plugin_suite_live_search_dict_technology(),
        'business' => init_plugin_suite_live_search_dict_business(),
        'health' => init_plugin_suite_live_search_dict_health(),
        'travel' => init_plugin_suite_live_search_dict_travel(),
        'education' => init_plugin_suite_live_search_dict_education(),
        'food' => init_plugin_suite_live_search_dict_food(),
        'sports' => init_plugin_suite_live_search_dict_sports(),
        'fashion' => init_plugin_suite_live_search_dict_fashion(),
        'entertainment' => init_plugin_suite_live_search_dict_entertainment(),
    ];

    return apply_filters('init_plugin_suite_live_search_all_dictionaries', $dictionaries);
}

/**
 * E-commerce & Shopping Dictionary
 */
function init_plugin_suite_live_search_dict_ecommerce() {
    return [
        // Core shopping terms
        'buy' => ['purchase', 'order', 'acquire', 'get', 'obtain', 'mua', 'đặt hàng', 'sắm', 'tậu'],
        'shop' => ['store', 'market', 'boutique', 'outlet', 'retail', 'cửa hàng', 'siêu thị', 'shop', 'chợ'],
        'cart' => ['basket', 'shopping bag', 'checkout', 'giỏ hàng', 'túi', 'thanh toán'],
        'sale' => ['discount', 'promotion', 'deal', 'offer', 'bargain', 'giảm giá', 'khuyến mãi', 'ưu đãi', 'sale'],
        'price' => ['cost', 'fee', 'rate', 'value', 'amount', 'giá', 'giá cả', 'chi phí', 'tiền'],
        'cheap' => ['affordable', 'budget', 'inexpensive', 'low-cost', 'rẻ', 'bình dân', 'tiết kiệm', 'giá tốt'],
        'expensive' => ['costly', 'premium', 'high-end', 'luxury', 'đắt', 'cao cấp', 'sang trọng', 'xịn'],
        'delivery' => ['shipping', 'transport', 'dispatch', 'giao hàng', 'vận chuyển', 'ship', 'chuyển phát'],
        'payment' => ['billing', 'checkout', 'transaction', 'thanh toán', 'trả tiền', 'đóng tiền'],
        'product' => ['item', 'goods', 'merchandise', 'commodity', 'sản phẩm', 'hàng hóa', 'mặt hàng', 'đồ'],
        'customer' => ['client', 'buyer', 'shopper', 'consumer', 'khách hàng', 'khách', 'người mua'],
        'seller' => ['vendor', 'merchant', 'retailer', 'dealer', 'người bán', 'shop', 'cửa hàng'],
        'warehouse' => ['storage', 'depot', 'stockroom', 'kho', 'kho hàng', 'nhà kho'],
        'inventory' => ['stock', 'supply', 'goods', 'tồn kho', 'hàng tồn', 'kho'],
        'refund' => ['return', 'reimbursement', 'money back', 'hoàn tiền', 'trả hàng', 'đổi trả'],
        'coupon' => ['voucher', 'promo code', 'discount code', 'mã giảm giá', 'phiếu', 'voucher'],
        'wishlist' => ['favorites', 'saved items', 'want list', 'yêu thích', 'lưu', 'danh sách mong muốn'],
        'review' => ['rating', 'feedback', 'comment', 'testimonial', 'đánh giá', 'nhận xét', 'review'],
        
        // Vietnamese specific terms
        'mua' => ['buy', 'purchase', 'order', 'sắm', 'tậu', 'đặt hàng'],
        'bán' => ['sell', 'sale', 'selling', 'kinh doanh'],
        'giá' => ['price', 'cost', 'giá cả', 'tiền'],
        'rẻ' => ['cheap', 'affordable', 'giá tốt', 'bình dân'],
        'đắt' => ['expensive', 'costly', 'cao cấp'],
        'ship' => ['delivery', 'shipping', 'giao hàng', 'vận chuyển'],
        'sale' => ['discount', 'promotion', 'giảm giá', 'khuyến mãi'],
        'order' => ['đặt hàng', 'order', 'mua'],
        'thanh toán' => ['payment', 'pay', 'trả tiền'],
        'freeship' => ['free shipping', 'miễn phí vận chuyển', 'free delivery'],
        'cod' => ['cash on delivery', 'thanh toán khi nhận hàng', 'trả tiền mặt'],
        'flash sale' => ['giảm giá sốc', 'sale nhanh', 'khuyến mãi'],
        'authentic' => ['chính hãng', 'thật', 'real', 'auth'],
        'fake' => ['giả', 'nhái', 'không chính hãng', 'rep'],
        'new' => ['mới', 'brand new', 'new 100%'],
        'used' => ['cũ', 'second hand', 'đã qua sử dụng'],
        'hot' => ['nóng', 'trending', 'bán chạy', 'hot trend'],
        'bestseller' => ['bán chạy', 'best seller', 'hot'],
    ];
}

/**
 * Technology & IT Dictionary
 */
function init_plugin_suite_live_search_dict_technology() {
    return [
        // Core tech terms
        'computer' => ['pc', 'laptop', 'desktop', 'machine', 'máy tính', 'máy', 'laptop', 'pc'],
        'software' => ['program', 'application', 'app', 'tool', 'phần mềm', 'ứng dụng', 'app', 'tool'],
        'website' => ['site', 'web page', 'portal', 'platform', 'trang web', 'website', 'web'],
        'internet' => ['web', 'online', 'net', 'www', 'mạng', 'internet', 'online'],
        'mobile' => ['smartphone', 'phone', 'device', 'cell', 'điện thoại', 'smartphone', 'di động'],
        'code' => ['programming', 'script', 'development', 'lập trình', 'code', 'viết code'],
        'database' => ['db', 'data storage', 'repository', 'cơ sở dữ liệu', 'database', 'dữ liệu'],
        'server' => ['host', 'hosting', 'cloud', 'máy chủ', 'server', 'host'],
        'network' => ['connection', 'wifi', 'lan', 'internet', 'mạng', 'kết nối', 'wifi'],
        'security' => ['protection', 'firewall', 'encryption', 'bảo mật', 'an toàn', 'security'],
        'backup' => ['copy', 'archive', 'save', 'storage', 'sao lưu', 'backup', 'lưu trữ'],
        'update' => ['upgrade', 'patch', 'version', 'refresh', 'cập nhật', 'update', 'nâng cấp'],
        'install' => ['setup', 'configure', 'deploy', 'cài đặt', 'install', 'setup'],
        'download' => ['get', 'fetch', 'retrieve', 'load', 'tải xuống', 'download', 'tải về'],
        'upload' => ['send', 'transfer', 'submit', 'post', 'tải lên', 'upload', 'up'],
        'bug' => ['error', 'issue', 'problem', 'glitch', 'lỗi', 'bug', 'sự cố'],
        'feature' => ['function', 'capability', 'option', 'tính năng', 'chức năng', 'feature'],
        'user' => ['account', 'profile', 'member', 'person', 'người dùng', 'user', 'tài khoản'],
        'admin' => ['administrator', 'manager', 'moderator', 'quản trị', 'admin', 'quản lý'],
        'api' => ['interface', 'service', 'endpoint', 'api', 'giao diện'],
        
        // Vietnamese specific tech terms
        'máy tính' => ['computer', 'pc', 'laptop', 'máy'],
        'điện thoại' => ['phone', 'mobile', 'smartphone', 'di động'],
        'phần mềm' => ['software', 'app', 'application', 'ứng dụng'],
        'lập trình' => ['programming', 'code', 'coding', 'dev'],
        'trang web' => ['website', 'web', 'site'],
        'ứng dụng' => ['app', 'application', 'software'],
        'cài đặt' => ['install', 'setup', 'config'],
        'tải xuống' => ['download', 'tải về', 'tải'],
        'cập nhật' => ['update', 'upgrade', 'nâng cấp'],
        'bảo mật' => ['security', 'an toàn', 'protection'],
        'wifi' => ['wireless', 'mạng không dây', 'internet'],
        'game' => ['trò chơi', 'gaming', 'games'],
        'streaming' => ['phát trực tiếp', 'live stream', 'xem online'],
        'cloud' => ['đám mây', 'lưu trữ đám mây', 'cloud storage'],
        'ai' => ['artificial intelligence', 'trí tuệ nhân tạo', 'machine learning'],
        'blockchain' => ['chuỗi khối', 'crypto', 'bitcoin'],
        'iot' => ['internet of things', 'vạn vật kết nối'],
        '5g' => ['mạng 5g', '5g network', 'tốc độ cao'],
        'vr' => ['virtual reality', 'thực tế ảo', 'kính vr'],
        'ar' => ['augmented reality', 'thực tế tăng cường'],
    ];
}

/**
 * Business & Marketing Dictionary
 */
function init_plugin_suite_live_search_dict_business() {
    return [
        // Core business terms
        'company' => ['business', 'corporation', 'firm', 'enterprise', 'công ty', 'doanh nghiệp', 'tập đoàn'],
        'employee' => ['worker', 'staff', 'team member', 'personnel', 'nhân viên', 'cán bộ', 'công nhân'],
        'manager' => ['supervisor', 'director', 'leader', 'boss', 'quản lý', 'giám đốc', 'trưởng phòng'],
        'meeting' => ['conference', 'discussion', 'session', 'họp', 'cuộc họp', 'hội nghị'],
        'project' => ['task', 'assignment', 'initiative', 'work', 'dự án', 'công việc', 'nhiệm vụ'],
        'client' => ['customer', 'account', 'patron', 'khách hàng', 'khách', 'đối tác'],
        'marketing' => ['advertising', 'promotion', 'branding', 'tiếp thị', 'quảng cáo', 'marketing'],
        'sales' => ['selling', 'revenue', 'income', 'profit', 'bán hàng', 'doanh số', 'kinh doanh'],
        'strategy' => ['plan', 'approach', 'method', 'tactic', 'chiến lược', 'kế hoạch', 'phương án'],
        'goal' => ['objective', 'target', 'aim', 'purpose', 'mục tiêu', 'định hướng', 'chỉ tiêu'],
        'growth' => ['expansion', 'development', 'increase', 'tăng trưởng', 'phát triển', 'mở rộng'],
        'budget' => ['finance', 'funding', 'money', 'cost', 'ngân sách', 'tài chính', 'kinh phí'],
        'report' => ['analysis', 'summary', 'document', 'báo cáo', 'tổng kết', 'phân tích'],
        'deadline' => ['due date', 'timeline', 'schedule', 'hạn chót', 'thời hạn', 'deadline'],
        'contract' => ['agreement', 'deal', 'terms', 'hợp đồng', 'thỏa thuận', 'giao kèo'],
        'invoice' => ['bill', 'statement', 'charge', 'hóa đơn', 'bill', 'phiếu thu'],
        'profit' => ['earnings', 'income', 'revenue', 'gain', 'lợi nhuận', 'thu nhập', 'doanh thu'],
        'loss' => ['deficit', 'expense', 'cost', 'debt', 'lỗ', 'thua lỗ', 'chi phí'],
        'investment' => ['funding', 'capital', 'finance', 'đầu tư', 'vốn', 'tài trợ'],
        'market' => ['industry', 'sector', 'field', 'arena', 'thị trường', 'ngành', 'lĩnh vực'],
        
        // Vietnamese specific business terms
        'công ty' => ['company', 'business', 'doanh nghiệp', 'firm'],
        'kinh doanh' => ['business', 'commerce', 'trading', 'bán hàng'],
        'bán hàng' => ['sales', 'selling', 'marketing', 'trade'],
        'quản lý' => ['management', 'manager', 'admin', 'giám đốc'],
        'nhân viên' => ['employee', 'staff', 'worker', 'cán bộ'],
        'dự án' => ['project', 'initiative', 'plan', 'kế hoạch'],
        'khách hàng' => ['customer', 'client', 'buyer', 'người mua'],
        'đầu tư' => ['investment', 'invest', 'funding', 'vốn'],
        'lợi nhuận' => ['profit', 'income', 'revenue', 'thu nhập'],
        'thị trường' => ['market', 'marketplace', 'industry'],
        'cạnh tranh' => ['competition', 'competitive', 'rival'],
        'thương hiệu' => ['brand', 'trademark', 'logo', 'nhãn hiệu'],
        'chất lượng' => ['quality', 'standard', 'grade', 'tốt'],
        'giá cả' => ['price', 'pricing', 'cost', 'giá'],
        'xuất khẩu' => ['export', 'shipping abroad', 'overseas'],
        'nhập khẩu' => ['import', 'imported goods', 'foreign'],
        'startup' => ['khởi nghiệp', 'công ty khởi nghiệp', 'start up'],
        'sme' => ['doanh nghiệp vừa và nhỏ', 'small business', 'vừa và nhỏ'],
        'b2b' => ['business to business', 'doanh nghiệp với doanh nghiệp'],
        'b2c' => ['business to consumer', 'doanh nghiệp với khách hàng'],
    ];
}

/**
 * Health & Wellness Dictionary
 */
function init_plugin_suite_live_search_dict_health() {
    return [
        // Core health terms
        'doctor' => ['physician', 'medical professional', 'practitioner', 'bác sĩ', 'thầy thuốc', 'y sĩ'],
        'hospital' => ['clinic', 'medical center', 'healthcare facility', 'bệnh viện', 'phòng khám', 'y tế'],
        'medicine' => ['medication', 'drug', 'treatment', 'remedy', 'thuốc', 'dược phẩm', 'y học'],
        'healthy' => ['fit', 'well', 'good health', 'wellness', 'khỏe mạnh', 'tốt', 'khỏe'],
        'exercise' => ['workout', 'fitness', 'training', 'activity', 'tập thể dục', 'vận động', 'gym'],
        'diet' => ['nutrition', 'eating plan', 'food regimen', 'chế độ ăn', 'dinh dưỡng', 'ăn kiêng'],
        'vitamin' => ['supplement', 'nutrient', 'mineral', 'vitamin', 'chất bổ', 'dinh dưỡng'],
        'therapy' => ['treatment', 'counseling', 'rehabilitation', 'liệu pháp', 'trị liệu', 'chữa trị'],
        'symptom' => ['sign', 'indication', 'manifestation', 'triệu chứng', 'dấu hiệu', 'biểu hiện'],
        'diagnosis' => ['assessment', 'evaluation', 'examination', 'chẩn đoán', 'khám bệnh', 'xét nghiệm'],
        'prevention' => ['protection', 'avoidance', 'precaution', 'phòng ngừa', 'ngăn chặn', 'dự phòng'],
        'wellness' => ['health', 'wellbeing', 'fitness', 'sức khỏe', 'tình trạng tốt', 'khỏe mạnh'],
        'stress' => ['pressure', 'tension', 'anxiety', 'căng thẳng', 'áp lực', 'lo lắng'],
        'sleep' => ['rest', 'slumber', 'bedtime', 'ngủ', 'nghỉ ngơi', 'giấc ngủ'],
        'weight' => ['mass', 'pounds', 'kilos', 'body weight', 'cân nặng', 'trọng lượng', 'kg'],
        'pain' => ['ache', 'discomfort', 'soreness', 'đau', 'đau đớn', 'khó chịu'],
        'injury' => ['wound', 'hurt', 'damage', 'trauma', 'chấn thương', 'tổn thương', 'vết thương'],
        'recovery' => ['healing', 'rehabilitation', 'getting better', 'hồi phục', 'khỏi bệnh', 'phục hồi'],
        'checkup' => ['examination', 'inspection', 'review', 'khám sức khỏe', 'kiểm tra', 'tổng quát'],
        'surgery' => ['operation', 'procedure', 'intervention', 'phẫu thuật', 'mổ', 'can thiệp'],
        
        // Vietnamese specific health terms
        'bác sĩ' => ['doctor', 'physician', 'thầy thuốc', 'y sĩ'],
        'bệnh viện' => ['hospital', 'clinic', 'y tế', 'phòng khám'],
        'thuốc' => ['medicine', 'medication', 'drug', 'dược'],
        'khỏe mạnh' => ['healthy', 'fit', 'well', 'tốt'],
        'bệnh' => ['disease', 'illness', 'sickness', 'ailment'],
        'chữa bệnh' => ['treatment', 'cure', 'therapy', 'heal'],
        'sức khỏe' => ['health', 'wellness', 'wellbeing', 'fitness'],
        'tập thể dục' => ['exercise', 'workout', 'fitness', 'gym'],
        'ăn kiêng' => ['diet', 'dieting', 'weight loss', 'giảm cân'],
        'giảm cân' => ['weight loss', 'lose weight', 'slimming', 'diet'],
        'tăng cân' => ['weight gain', 'gain weight', 'put on weight'],
        'căng thẳng' => ['stress', 'pressure', 'anxiety', 'tension'],
        'đau đầu' => ['headache', 'head pain', 'migraine'],
        'cảm cúm' => ['flu', 'cold', 'influenza', 'sick'],
        'ho' => ['cough', 'coughing', 'throat'],
        'sốt' => ['fever', 'temperature', 'hot'],
        'mệt mỏi' => ['tired', 'fatigue', 'exhausted', 'weary'],
        'khám bệnh' => ['medical checkup', 'examination', 'doctor visit'],
        'xét nghiệm' => ['test', 'blood test', 'lab test', 'screening'],
        'y tế' => ['healthcare', 'medical', 'health services'],
        'dược phẩm' => ['pharmaceutical', 'medicine', 'drug', 'medication'],
        'spa' => ['massage', 'relaxation', 'wellness center', 'thư giãn'],
        'yoga' => ['meditation', 'stretching', 'mindfulness', 'thiền'],
        'detox' => ['cleanse', 'purify', 'thải độc', 'làm sạch'],
        'organic' => ['hữu cơ', 'natural', 'tự nhiên', 'sạch'],
    ];
}

/**
 * Travel & Tourism Dictionary
 */
function init_plugin_suite_live_search_dict_travel() {
    return [
        // Core travel terms
        'travel' => ['trip', 'journey', 'vacation', 'tour', 'du lịch', 'đi chơi', 'chuyến đi'],
        'hotel' => ['accommodation', 'lodging', 'inn', 'resort', 'khách sạn', 'nhà nghỉ', 'resort'],
        'flight' => ['airplane', 'airline', 'air travel', 'chuyến bay', 'máy bay', 'hàng không'],
        'destination' => ['location', 'place', 'spot', 'site', 'điểm đến', 'nơi đến', 'địa điểm'],
        'vacation' => ['holiday', 'break', 'getaway', 'trip', 'kỳ nghỉ', 'nghỉ lễ', 'nghỉ phép'],
        'booking' => ['reservation', 'appointment', 'schedule', 'đặt chỗ', 'đặt phòng', 'book'],
        'tourist' => ['traveler', 'visitor', 'guest', 'sightseer', 'khách du lịch', 'du khách'],
        'guide' => ['tour guide', 'instructor', 'leader', 'hướng dẫn viên', 'guide', 'dẫn đường'],
        'passport' => ['id', 'identification', 'travel document', 'hộ chiếu', 'passport', 'giấy tờ'],
        'visa' => ['permit', 'authorization', 'entry document', 'visa', 'thị thực', 'giấy phép'],
        'luggage' => ['baggage', 'suitcase', 'bags', 'hành lý', 'vali', 'túi xách'],
        'airport' => ['terminal', 'air station', 'flight hub', 'sân bay', 'phi trường', 'terminal'],
        'restaurant' => ['dining', 'eatery', 'cafe', 'bistro', 'nhà hàng', 'quán ăn'],
        'sightseeing' => ['touring', 'exploring', 'visiting', 'tham quan', 'ngắm cảnh', 'khám phá'],
        'adventure' => ['expedition', 'exploration', 'journey', 'phiêu lưu', 'mạo hiểm', 'khám phá'],
        'culture' => ['heritage', 'tradition', 'customs', 'văn hóa', 'truyền thống', 'phong tục'],
        'beach' => ['shore', 'coast', 'seaside', 'waterfront', 'bãi biển', 'biển', 'bờ biển'],
        'mountain' => ['peak', 'hill', 'summit', 'range', 'núi', 'đỉnh núi', 'dãy núi'],
        'city' => ['town', 'urban area', 'metropolis', 'thành phố', 'thị trấn', 'đô thị'],
        'countryside' => ['rural area', 'farmland', 'nature', 'nông thôn', 'miền quê', 'vùng quê'],
        
        // Vietnamese specific travel terms
        'du lịch' => ['travel', 'tour', 'tourism', 'trip'],
        'đi chơi' => ['go out', 'travel', 'vacation', 'tour'],
        'khách sạn' => ['hotel', 'accommodation', 'lodging', 'resort'],
        'máy bay' => ['airplane', 'flight', 'aircraft', 'plane'],
        'sân bay' => ['airport', 'terminal', 'air station'],
        'hộ chiếu' => ['passport', 'travel document', 'id'],
        'visa' => ['thị thực', 'entry permit', 'travel visa'],
        'đặt phòng' => ['book room', 'reservation', 'booking'],
        'du khách' => ['tourist', 'traveler', 'visitor'],
        'hướng dẫn viên' => ['tour guide', 'guide', 'instructor'],
        'tham quan' => ['sightseeing', 'visit', 'tour', 'explore'],
        'bãi biển' => ['beach', 'seaside', 'coast', 'shore'],
        'núi' => ['mountain', 'hill', 'peak', 'summit'],
        'thành phố' => ['city', 'urban area', 'metropolis'],
        'nông thôn' => ['countryside', 'rural area', 'village'],
        'đảo' => ['island', 'isle', 'archipelago'],
        'vịnh' => ['bay', 'gulf', 'inlet'],
        'thác' => ['waterfall', 'falls', 'cascade'],
        'rừng' => ['forest', 'jungle', 'woods'],
        'công viên' => ['park', 'garden', 'recreational area'],
        'chùa' => ['temple', 'pagoda', 'buddhist temple'],
        'đền' => ['temple', 'shrine', 'sanctuary'],
        'bảo tàng' => ['museum', 'gallery', 'exhibition'],
        'lịch sử' => ['history', 'historical', 'heritage'],
        'văn hóa' => ['culture', 'cultural', 'tradition'],
        'ẩm thực' => ['cuisine', 'food culture', 'culinary'],
        'lễ hội' => ['festival', 'celebration', 'event'],
        'mua sắm' => ['shopping', 'shop', 'buy'],
        'chợ' => ['market', 'marketplace', 'bazaar'],
        'xe buýt' => ['bus', 'public transport', 'coach'],
        'tàu hỏa' => ['train', 'railway', 'locomotive'],
        'xe máy' => ['motorbike', 'motorcycle', 'scooter'],
        'ô tô' => ['car', 'automobile', 'vehicle'],
        'taxi' => ['cab', 'ride', 'transport'],
        'grab' => ['ride sharing', 'transport app', 'taxi app'],
        'homestay' => ['home stay', 'local accommodation', 'guest house'],
        'backpacker' => ['budget traveler', 'du lịch bụi', 'phượt'],
        'phượt' => ['backpacking', 'adventure travel', 'budget travel'],
        'cắm trại' => ['camping', 'camp', 'outdoor'],
        'leo núi' => ['mountain climbing', 'hiking', 'trekking'],
        'lặn' => ['diving', 'scuba diving', 'underwater'],
        'spa' => ['massage', 'wellness', 'relaxation'],
        'nghỉ dưỡng' => ['resort', 'relaxation', 'vacation'],
        'all inclusive' => ['trọn gói', 'bao gồm tất cả', 'full package'],
        'free wifi' => ['wifi miễn phí', 'internet free', 'wifi'],
        'check in' => ['nhận phòng', 'đăng ký', 'arrival'],
        'check out' => ['trả phòng', 'checkout', 'departure'],
    ];
}

/**
 * Education & Learning Dictionary
 */
function init_plugin_suite_live_search_dict_education() {
    return [
        // Core education terms
        'school' => ['education', 'academy', 'institute', 'college', 'trường', 'trường học', 'học viện'],
        'student' => ['pupil', 'learner', 'scholar', 'học sinh', 'sinh viên', 'học viên'],
        'teacher' => ['instructor', 'educator', 'professor', 'giáo viên', 'thầy', 'cô'],
        'course' => ['class', 'subject', 'lesson', 'program', 'khóa học', 'môn học', 'lớp học'],
        'exam' => ['test', 'quiz', 'assessment', 'evaluation', 'thi', 'kiểm tra', 'bài thi'],
        'homework' => ['assignment', 'task', 'exercise', 'bài tập', 'bài về nhà', 'assignment'],
        'study' => ['learn', 'research', 'review', 'practice', 'học', 'học tập', 'nghiên cứu'],
        'book' => ['textbook', 'manual', 'guide', 'reference', 'sách', 'giáo trình', 'tài liệu'],
        'library' => ['resource center', 'study hall', 'archive', 'thư viện', 'kho sách'],
        'degree' => ['diploma', 'certificate', 'qualification', 'bằng cấp', 'học vị', 'chứng chỉ'],
        'grade' => ['mark', 'score', 'result', 'rating', 'điểm', 'điểm số', 'kết quả'],
        'knowledge' => ['information', 'understanding', 'learning', 'kiến thức', 'hiểu biết'],
        'skill' => ['ability', 'talent', 'competency', 'kỹ năng', 'khả năng', 'tài năng'],
        'training' => ['preparation', 'instruction', 'coaching', 'đào tạo', 'huấn luyện'],
        'university' => ['college', 'higher education', 'academy', 'đại học', 'trường đại học'],
        'graduation' => ['completion', 'finishing', 'achievement', 'tốt nghiệp', 'ra trường'],
        'scholarship' => ['grant', 'funding', 'financial aid', 'học bổng', 'trợ cấp học tập'],
        'research' => ['investigation', 'study', 'analysis', 'nghiên cứu', 'tìm hiểu'],
        'project' => ['assignment', 'work', 'task', 'dự án', 'đồ án', 'bài tập lớn'],
        'lecture' => ['presentation', 'talk', 'speech', 'bài giảng', 'thuyết trình'],
        
        // Vietnamese specific education terms
        'học' => ['study', 'learn', 'education', 'learning'],
        'trường' => ['school', 'educational institution', 'academy'],
        'lớp' => ['class', 'classroom', 'grade level'],
        'học sinh' => ['student', 'pupil', 'learner'],
        'sinh viên' => ['university student', 'college student', 'undergraduate'],
        'giáo viên' => ['teacher', 'instructor', 'educator'],
        'thầy' => ['male teacher', 'professor', 'instructor'],
        'cô' => ['female teacher', 'miss', 'instructor'],
        'hiệu trưởng' => ['principal', 'headmaster', 'school director'],
        'bài học' => ['lesson', 'class', 'subject matter'],
        'môn học' => ['subject', 'course', 'academic discipline'],
        'khóa học' => ['course', 'program', 'curriculum'],
        'học kỳ' => ['semester', 'term', 'academic period'],
        'năm học' => ['academic year', 'school year'],
        'thi' => ['exam', 'test', 'examination'],
        'kiểm tra' => ['test', 'quiz', 'assessment'],
        'bài tập' => ['homework', 'assignment', 'exercise'],
        'điểm' => ['grade', 'score', 'mark'],
        'học bổng' => ['scholarship', 'grant', 'financial aid'],
        'bằng cấp' => ['degree', 'diploma', 'certificate'],
        'tốt nghiệp' => ['graduation', 'completion', 'finish'],
        'thư viện' => ['library', 'resource center', 'book collection'],
        'sách giáo khoa' => ['textbook', 'coursebook', 'academic book'],
        'vở' => ['notebook', 'exercise book', 'workbook'],
        'bút' => ['pen', 'pencil', 'writing tool'],
        'bảng' => ['blackboard', 'whiteboard', 'board'],
        'phấn' => ['chalk', 'marker', 'writing tool'],
        'bàn' => ['desk', 'table', 'workstation'],
        'ghế' => ['chair', 'seat', 'sitting'],
        'lịch học' => ['schedule', 'timetable', 'class schedule'],
        'thời khóa biểu' => ['timetable', 'schedule', 'class schedule'],
        'nghỉ học' => ['absent', 'skip class', 'time off'],
        'đi học' => ['go to school', 'attend class', 'study'],
        'về nhà' => ['go home', 'return home', 'homework time'],
        'phụ huynh' => ['parents', 'guardians', 'family'],
        'gia đình' => ['family', 'household', 'relatives'],
        'bạn học' => ['classmate', 'schoolmate', 'study buddy'],
        'bạn cùng lớp' => ['classmate', 'peer', 'class friend'],
        'nhóm' => ['group', 'team', 'study group'],
        'đội' => ['team', 'squad', 'group'],
        'cuộc thi' => ['competition', 'contest', 'tournament'],
        'giải thưởng' => ['prize', 'award', 'recognition'],
        'chứng chỉ' => ['certificate', 'qualification', 'credential'],
        'kỹ năng' => ['skill', 'ability', 'competency'],
        'kiến thức' => ['knowledge', 'information', 'learning'],
        'kinh nghiệm' => ['experience', 'practice', 'expertise'],
        'thực hành' => ['practice', 'hands-on', 'practical'],
        'lý thuyết' => ['theory', 'theoretical', 'academic'],
        'ôn tập' => ['review', 'revision', 'study review'],
        'chuẩn bị' => ['prepare', 'preparation', 'get ready'],
        'nộp bài' => ['submit', 'hand in', 'turn in'],
        'làm bài' => ['do exercise', 'work on', 'complete'],
        'giải bài' => ['solve', 'work out', 'answer'],
        'học thuộc' => ['memorize', 'learn by heart', 'rote learning'],
        'hiểu' => ['understand', 'comprehend', 'grasp'],
        'nhớ' => ['remember', 'recall', 'memory'],
        'quên' => ['forget', 'overlook', 'miss'],
        'khó' => ['difficult', 'hard', 'challenging'],
        'dễ' => ['easy', 'simple', 'straightforward'],
        'thông minh' => ['smart', 'intelligent', 'clever'],
        'chăm chỉ' => ['hardworking', 'diligent', 'studious'],
        'lười' => ['lazy', 'sluggish', 'unmotivated'],
        'giỏi' => ['good at', 'excellent', 'talented'],
        'yếu' => ['weak', 'poor', 'struggling'],
        'trung bình' => ['average', 'medium', 'ordinary'],
        'xuất sắc' => ['excellent', 'outstanding', 'exceptional'],
        'đạt' => ['pass', 'achieve', 'succeed'],
        'trượt' => ['fail', 'not pass', 'unsuccessful'],
        'online' => ['trực tuyến', 'học online', 'e-learning'],
        'offline' => ['ngoại tuyến', 'học trực tiếp', 'in-person'],
        'zoom' => ['video call', 'online meeting', 'virtual class'],
        'google meet' => ['video conference', 'online class', 'virtual meeting'],
        'microsoft teams' => ['collaboration platform', 'online workspace'],
        'google classroom' => ['learning management', 'online course platform'],
        'e-learning' => ['học điện tử', 'digital learning', 'online education'],
        'distance learning' => ['học từ xa', 'remote education', 'correspondence'],
        'homeschool' => ['học tại nhà', 'home education', 'self-directed'],
        'tutor' => ['gia sư', 'private teacher', 'personal instructor'],
        'mentor' => ['người hướng dẫn', 'advisor', 'guide'],
        'intern' => ['thực tập sinh', 'trainee', 'apprentice'],
        'internship' => ['thực tập', 'work experience', 'training program'],
        'workshop' => ['hội thảo', 'seminar', 'training session'],
        'conference' => ['hội nghị', 'symposium', 'academic meeting'],
        'presentation' => ['thuyết trình', 'speech', 'talk'],
        'thesis' => ['luận văn', 'dissertation', 'research paper'],
        'essay' => ['bài luận', 'composition', 'written work'],
        'report' => ['báo cáo', 'summary', 'analysis'],
        'portfolio' => ['hồ sơ', 'collection', 'showcase'],
        'curriculum' => ['chương trình học', 'course of study', 'syllabus'],
        'syllabus' => ['đề cương', 'course outline', 'study plan'],
        'extracurricular' => ['ngoại khóa', 'after school', 'additional activities'],
        'club' => ['câu lạc bộ', 'society', 'organization'],
        'field trip' => ['dã ngoại', 'educational visit', 'excursion'],
        'laboratory' => ['phòng thí nghiệm', 'lab', 'research facility'],
        'experiment' => ['thí nghiệm', 'test', 'scientific investigation'],
    ];
}

/**
 * Food & Cooking Dictionary
 */
function init_plugin_suite_live_search_dict_food() {
    return [
        // Core food terms
        'food' => ['meal', 'dish', 'cuisine', 'nourishment', 'đồ ăn', 'thức ăn', 'món ăn', 'thực phẩm'],
        'recipe' => ['formula', 'instructions', 'cooking method', 'công thức', 'cách làm', 'recipe'],
        'cooking' => ['preparing', 'making', 'culinary', 'nấu ăn', 'làm bếp', 'chế biến'],
        'ingredient' => ['component', 'element', 'item', 'nguyên liệu', 'thành phần', 'gia vị'],
        'restaurant' => ['dining', 'eatery', 'bistro', 'cafe', 'nhà hàng', 'quán ăn', 'quán'],
        'chef' => ['cook', 'culinary expert', 'kitchen professional', 'đầu bếp', 'người nấu', 'thầy bếp'],
        'kitchen' => ['cooking area', 'culinary space', 'bếp', 'nhà bếp', 'khu vực nấu ăn'],
        'menu' => ['food list', 'offerings', 'selection', 'thực đơn', 'menu', 'danh sách món'],
        'taste' => ['flavor', 'savor', 'palate', 'vị', 'hương vị', 'mùi vị'],
        'delicious' => ['tasty', 'yummy', 'flavorful', 'savory', 'ngon', 'thơm ngon', 'tuyệt vời'],
        'healthy' => ['nutritious', 'wholesome', 'good for you', 'lành mạnh', 'bổ dưỡng', 'tốt cho sức khỏe'],
        'fresh' => ['new', 'crisp', 'recently made', 'tươi', 'tươi ngon', 'mới'],
        'organic' => ['natural', 'chemical-free', 'pure', 'hữu cơ', 'tự nhiên', 'sạch'],
        'spicy' => ['hot', 'fiery', 'peppery', 'seasoned', 'cay', '매운', 'nóng'],
        'sweet' => ['sugary', 'dessert-like', 'candy-like', 'ngọt', 'đường', 'kẹo'],
        'salty' => ['savory', 'seasoned', 'briny', 'mặn', '짠', 'muối'],
        'bitter' => ['sharp', 'tart', 'acidic', 'đắng', 'chua', 'chát'],
        'breakfast' => ['morning meal', 'first meal', 'bữa sáng', 'ăn sáng', 'sáng'],
        'lunch' => ['midday meal', 'noon meal', 'bữa trưa', 'ăn trưa', 'trưa'],
        'dinner' => ['evening meal', 'supper', 'main meal', 'bữa tối', 'ăn tối', 'tối'],
        
        // Vietnamese specific food terms
        'đồ ăn' => ['food', 'meal', 'dish', 'thức ăn'],
        'món ăn' => ['dish', 'food', 'meal', 'cuisine'],
        'nấu ăn' => ['cooking', 'cook', 'prepare food', 'làm bếp'],
        'nhà hàng' => ['restaurant', 'dining', 'eatery', 'quán ăn'],
        'quán ăn' => ['restaurant', 'eatery', 'food stall', 'nhà hàng'],
        'đầu bếp' => ['chef', 'cook', 'cooking expert'],
        'ngon' => ['delicious', 'tasty', 'yummy', 'good'],
        'cay' => ['spicy', 'hot', 'peppery', 'chili'],
        'ngọt' => ['sweet', 'sugary', 'dessert'],
        'mặn' => ['salty', 'savory', 'seasoned'],
        'chua' => ['sour', 'acidic', 'tart', 'bitter'],
        'đắng' => ['bitter', 'sharp', 'harsh'],
        'tươi' => ['fresh', 'new', 'crisp', 'recently made'],
        'phở' => ['pho', 'vietnamese noodle soup', 'beef noodle'],
        'bánh mì' => ['vietnamese sandwich', 'bread', 'sandwich'],
        'cơm' => ['rice', 'steamed rice', 'meal'],
        'bún' => ['rice noodle', 'vermicelli', 'noodle'],
        'nem' => ['spring roll', 'fried roll', 'roll'],
        'chả' => ['vietnamese sausage', 'fish cake', 'meat cake'],
        'nướng' => ['grilled', 'barbecue', 'roasted', 'bbq'],
        'chiên' => ['fried', 'deep fried', 'pan fried'],
        'luộc' => ['boiled', 'steamed', 'cooked in water'],
        'xào' => ['stir fried', 'sauteed', 'fried'],
        'canh' => ['soup', 'broth', 'clear soup'],
        'lẩu' => ['hot pot', 'steamboat', 'fondue'],
        'gỏi' => ['salad', 'vietnamese salad', 'mixed salad'],
        'chấm' => ['dipping sauce', 'sauce', 'condiment'],
        'nước mắm' => ['fish sauce', 'vietnamese sauce'],
        'tương ớt' => ['chili sauce', 'hot sauce', 'sriracha'],
        'bia' => ['beer', 'alcohol', 'beverage'],
        'trà' => ['tea', 'herbal tea', 'drink'],
        'cà phê' => ['coffee', 'espresso', 'cafe'],
        'nước ngọt' => ['soft drink', 'soda', 'beverage'],
        'kem' => ['ice cream', 'dessert', 'frozen'],
        'bánh' => ['cake', 'bread', 'pastry', 'dessert'],
        'kẹo' => ['candy', 'sweet', 'confection'],
        'trái cây' => ['fruit', 'fresh fruit', 'seasonal fruit'],
        'rau' => ['vegetables', 'greens', 'veggies'],
        'thịt' => ['meat', 'protein', 'animal protein'],
        'cá' => ['fish', 'seafood', 'marine'],
        'tôm' => ['shrimp', 'prawn', 'seafood'],
        'cua' => ['crab', 'seafood', 'shellfish'],
        'healthy food' => ['đồ ăn lành mạnh', 'thực phẩm tốt', 'ăn healthy'],
        'fast food' => ['đồ ăn nhanh', 'thức ăn nhanh', 'junk food'],
        'street food' => ['đồ ăn đường phố', 'ăn vặt', 'quán vỉa hè'],
        'homemade' => ['tự làm', 'làm tại nhà', 'nấu nhà'],
    ];
}

/**
 * Sports & Fitness Dictionary
 */
function init_plugin_suite_live_search_dict_sports() {
    return [
        // Core sports terms
        'sport' => ['game', 'activity', 'competition', 'athletics', 'thể thao', 'vận động', 'thi đấu'],
        'fitness' => ['exercise', 'workout', 'training', 'health', 'thể dục', 'tập luyện', 'sức khỏe'],
        'gym' => ['fitness center', 'health club', 'workout facility', 'phòng gym', 'trung tâm thể dục'],
        'athlete' => ['player', 'competitor', 'sportsperson', 'vận động viên', 'cầu thủ', 'tuyển thủ'],
        'coach' => ['trainer', 'instructor', 'mentor', 'huấn luyện viên', 'thầy', 'coach'],
        'team' => ['squad', 'group', 'crew', 'club', 'đội', 'nhóm', 'đội bóng'],
        'game' => ['match', 'competition', 'contest', 'trận đấu', 'game', 'cuộc thi'],
        'training' => ['practice', 'preparation', 'conditioning', 'tập luyện', 'rèn luyện', 'chuẩn bị'],
        'exercise' => ['workout', 'activity', 'movement', 'bài tập', 'vận động', 'hoạt động'],
        'running' => ['jogging', 'sprinting', 'racing', 'chạy bộ', 'chạy', 'thi chạy'],
        'swimming' => ['aquatics', 'water sports', 'pool exercise', 'bơi lội', 'bơi', 'thể thao dưới nước'],
        'cycling' => ['biking', 'bicycle riding', 'đạp xe', 'xe đạp', 'cycling'],
        'football' => ['soccer', 'american football', 'bóng đá', 'football', 'soccer'],
        'basketball' => ['hoops', 'court sport', 'bóng rổ', 'basketball', 'bóng ném'],
        'tennis' => ['racquet sport', 'court game', 'tennis', 'quần vợt', 'đánh tennis'],
        'golf' => ['links', 'course sport', 'golf', 'đánh golf', 'sân golf'],
        'baseball' => ['diamond sport', 'bat and ball', 'bóng chày', 'baseball'],
        'volleyball' => ['net sport', 'beach volleyball', 'bóng chuyền', 'volleyball'],
        'strength' => ['power', 'muscle', 'force', 'sức mạnh', 'cơ bắp', 'tăng cân'],
        'endurance' => ['stamina', 'persistence', 'staying power', 'sức bền', 'độ bền', 'thể lực'],
        
        // Vietnamese specific sports terms
        'thể thao' => ['sport', 'athletics', 'games', 'sports'],
        'bóng đá' => ['football', 'soccer', 'football game'],
        'bóng rổ' => ['basketball', 'hoops', 'court sport'],
        'bóng chuyền' => ['volleyball', 'net sport', 'beach volleyball'],
        'quần vợt' => ['tennis', 'racquet sport', 'court game'],
        'cầu lông' => ['badminton', 'shuttlecock', 'racquet sport'],
        'bóng bàn' => ['table tennis', 'ping pong', 'paddle sport'],
        'bơi lội' => ['swimming', 'aquatics', 'water sport'],
        'chạy bộ' => ['running', 'jogging', 'marathon'],
        'đạp xe' => ['cycling', 'biking', 'bicycle'],
        'yoga' => ['meditation', 'stretching', 'mindfulness'],
        'aerobic' => ['cardio', 'fitness dance', 'workout'],
        'gym' => ['phòng tập', 'fitness center', 'health club'],
        'tập gym' => ['workout', 'fitness training', 'gym'],
        'tăng cân' => ['weight gain', 'muscle building', 'bulk up'],
        'giảm cân' => ['weight loss', 'slimming', 'diet'],
        'cơ bắp' => ['muscle', 'strength', 'bodybuilding'],
        'thể hình' => ['bodybuilding', 'physique', 'muscle building'],
        'cardio' => ['tim mạch', 'aerobic exercise', 'endurance'],
        'khởi động' => ['warm up', 'preparation', 'stretch'],
        'thư giãn' => ['cool down', 'relaxation', 'recovery'],
        'võ thuật' => ['martial arts', 'combat sports', 'fighting'],
        'karate' => ['martial art', 'japanese fighting', 'self defense'],
        'taekwondo' => ['korean martial art', 'kicking sport'],
        'boxing' => ['đấm bốc', 'combat sport', 'fighting'],
        'muay thai' => ['thai boxing', 'kickboxing', 'martial art'],
        'judo' => ['japanese martial art', 'grappling', 'throwing'],
        'leo núi' => ['mountain climbing', 'rock climbing', 'hiking'],
        'lặn' => ['diving', 'scuba diving', 'underwater sport'],
        'lướt sóng' => ['surfing', 'wave riding', 'water sport'],
        'trượt tuyết' => ['skiing', 'snow sport', 'winter sport'],
        'marathon' => ['long distance running', 'endurance race'],
        'triathlon' => ['three sport event', 'swimming cycling running'],
        'crossfit' => ['functional fitness', 'varied workout'],
        'pilates' => ['core strengthening', 'flexibility', 'balance'],
        'zumba' => ['fitness dance', 'latin dance workout'],
        'spinning' => ['indoor cycling', 'stationary bike workout'],
        'protein' => ['muscle building', 'recovery supplement'],
        'creatine' => ['muscle supplement', 'strength enhancer'],
        'whey' => ['protein powder', 'muscle recovery'],
        'bcaa' => ['amino acid supplement', 'recovery drink'],
        'pre workout' => ['energy supplement', 'training booster'],
        'mass gainer' => ['weight gain supplement', 'muscle builder'],
        'fat burner' => ['weight loss supplement', 'metabolism booster'],
        'dụng cụ' => ['equipment', 'gear', 'apparatus'],
        'tạ' => ['weights', 'dumbbells', 'barbell'],
        'máy chạy bộ' => ['treadmill', 'running machine'],
        'xe đạp tập' => ['exercise bike', 'stationary bike'],
        'thảm yoga' => ['yoga mat', 'exercise mat'],
    ];
}

/**
 * Fashion & Style Dictionary
 */
function init_plugin_suite_live_search_dict_fashion() {
    return [
        // Core fashion terms
        'fashion' => ['style', 'trend', 'clothing', 'apparel', 'thời trang', 'phong cách', 'xu hướng'],
        'clothes' => ['clothing', 'garments', 'attire', 'wear', 'quần áo', 'trang phục', 'đồ'],
        'style' => ['fashion', 'look', 'appearance', 'design', 'phong cách', 'kiểu dáng', 'style'],
        'trend' => ['fashion', 'popular', 'current', 'modern', 'xu hướng', 'mốt', 'hot trend'],
        'designer' => ['stylist', 'creator', 'fashion artist', 'nhà thiết kế', 'designer', 'stylist'],
        'brand' => ['label', 'designer', 'maker', 'company', 'thương hiệu', 'nhãn hiệu', 'hãng'],
        'outfit' => ['ensemble', 'look', 'clothing combination', 'trang phục', 'set đồ', 'outfit'],
        'dress' => ['gown', 'frock', 'garment', 'đầm', 'váy', 'dress'],
        'shirt' => ['top', 'blouse', 'tee', 'áo', 'áo sơ mi', 'áo thun'],
        'pants' => ['trousers', 'jeans', 'slacks', 'quần', 'quần dài', 'jeans'],
        'shoes' => ['footwear', 'sneakers', 'boots', 'giày', 'dép', 'sneaker'],
        'accessories' => ['extras', 'add-ons', 'jewelry', 'phụ kiện', 'đồ trang sức', 'accessories'],
        'jewelry' => ['accessories', 'ornaments', 'gems', 'trang sức', 'jewelry', 'nữ trang'],
        'makeup' => ['cosmetics', 'beauty products', 'trang điểm', 'makeup', 'mỹ phẩm'],
        'beauty' => ['attractiveness', 'elegance', 'style', 'làm đẹp', 'beauty', 'sắc đẹp'],
        'color' => ['shade', 'hue', 'tone', 'tint', 'màu', 'màu sắc', 'tone màu'],
        'size' => ['fit', 'measurement', 'dimensions', 'size', 'kích cỡ', 'kích thước'],
        'fabric' => ['material', 'textile', 'cloth', 'vải', 'chất liệu', 'fabric'],
        'vintage' => ['retro', 'classic', 'old-style', 'vintage', 'cổ điển', 'retro'],
        'luxury' => ['premium', 'high-end', 'expensive', 'cao cấp', 'luxury', 'sang trọng'],
        
        // Vietnamese specific fashion terms
        'thời trang' => ['fashion', 'style', 'trend', 'clothing'],
        'quần áo' => ['clothes', 'clothing', 'garments', 'attire'],
        'áo' => ['shirt', 'top', 'blouse', 'jacket'],
        'quần' => ['pants', 'trousers', 'bottoms'],
        'váy' => ['skirt', 'dress', 'frock'],
        'đầm' => ['dress', 'gown', 'evening dress'],
        'giày' => ['shoes', 'footwear', 'sneakers', 'boots'],
        'dép' => ['sandals', 'slippers', 'flip flops'],
        'túi' => ['bag', 'handbag', 'purse', 'backpack'],
        'ví' => ['wallet', 'purse', 'money bag'],
        'mũ' => ['hat', 'cap', 'headwear'],
        'kính' => ['glasses', 'sunglasses', 'eyewear'],
        'đồng hồ' => ['watch', 'timepiece', 'clock'],
        'nhẫn' => ['ring', 'band', 'jewelry'],
        'dây chuyền' => ['necklace', 'chain', 'pendant'],
        'bông tai' => ['earrings', 'ear jewelry'],
        'vòng tay' => ['bracelet', 'wristband', 'bangle'],
        'trang sức' => ['jewelry', 'accessories', 'ornaments'],
        'mỹ phẩm' => ['cosmetics', 'makeup', 'beauty products'],
        'son môi' => ['lipstick', 'lip color', 'lip product'],
        'phấn' => ['powder', 'foundation', 'makeup base'],
        'kem nền' => ['foundation', 'base makeup', 'primer'],
        'mascara' => ['eyelash makeup', 'eye makeup'],
        'phấn mắt' => ['eyeshadow', 'eye color', 'eye makeup'],
        'nước hoa' => ['perfume', 'fragrance', 'scent'],
        'chăm sóc da' => ['skincare', 'skin care', 'facial care'],
        'kem dưỡng' => ['moisturizer', 'cream', 'lotion'],
        'sữa rửa mặt' => ['facial cleanser', 'face wash', 'cleanser'],
        'toner' => ['facial toner', 'skin toner', 'astringent'],
        'serum' => ['skin serum', 'treatment', 'essence'],
        'kem chống nắng' => ['sunscreen', 'sunblock', 'spf'],
        'mặt nạ' => ['face mask', 'facial mask', 'sheet mask'],
        'nail' => ['móng tay', 'nail art', 'manicure'],
        'spa' => ['beauty spa', 'wellness center', 'salon'],
        'salon' => ['beauty salon', 'hair salon', 'beauty shop'],
        'cắt tóc' => ['haircut', 'hair styling', 'trim'],
        'uốn tóc' => ['hair perm', 'curling', 'wave'],
        'nhuộm tóc' => ['hair dye', 'hair coloring', 'tinting'],
        'làm nail' => ['manicure', 'nail art', 'nail care'],
        'vintage' => ['cổ điển', 'retro', 'classic style'],
        'streetwear' => ['thời trang đường phố', 'casual wear', 'urban style'],
        'formal' => ['trang trọng', 'dress code', 'business attire'],
        'casual' => ['thường ngày', 'everyday wear', 'relaxed style'],
        'sport' => ['thể thao', 'athletic wear', 'activewear'],
        'oversized' => ['rộng', 'loose fit', 'baggy'],
        'slim fit' => ['ôm', 'fitted', 'tight'],
        'cropped' => ['cắt ngắn', 'short', 'abbreviated'],
        'maxi' => ['dài', 'full length', 'floor length'],
        'mini' => ['ngắn', 'short', 'brief'],
        'midi' => ['vừa', 'medium length', 'knee length'],
        'high waist' => ['eo cao', 'high rise', 'high waisted'],
        'low waist' => ['eo thấp', 'low rise', 'hip hugger'],
        'v neck' => ['cổ v', 'v neckline', 'v cut'],
        'round neck' => ['cổ tròn', 'crew neck', 'round neckline'],
        'off shoulder' => ['trễ vai', 'bardot', 'shoulder free'],
        'sleeveless' => ['không tay', 'tank top', 'vest'],
        'long sleeve' => ['tay dài', 'full sleeve', 'long arm'],
        'short sleeve' => ['tay ngắn', 'half sleeve', 'short arm'],
        'bootcut' => ['ống loe', 'flared', 'bell bottom'],
        'skinny' => ['ôm sát', 'tight fit', 'form fitting'],
        'straight' => ['ống đứng', 'straight leg', 'regular fit'],
        'wide leg' => ['ống rộng', 'palazzo', 'loose leg'],
        'denim' => ['jean', 'jeans fabric', 'denim material'],
        'cotton' => ['cotton', 'vải cotton', 'natural fiber'],
        'silk' => ['lụa', 'silk fabric', 'luxury material'],
        'leather' => ['da', 'leather material', 'genuine leather'],
        'chiffon' => ['voan', 'sheer fabric', 'flowing material'],
        'lace' => ['ren', 'lace fabric', 'delicate material'],
        'satin' => ['lụa satin', 'shiny fabric', 'smooth material'],
    ];
}

/**
 * Entertainment & Media Dictionary (English + Vietnamese)
 */
function init_plugin_suite_live_search_dict_entertainment() {
    return [
        // Core entertainment terms
        'movie' => ['film', 'cinema', 'picture', 'flick', 'phim', 'phim ảnh', 'điện ảnh'],
        'music' => ['song', 'audio', 'sound', 'melody', 'nhạc', 'âm nhạc', 'bài hát'],
        'game' => ['video game', 'entertainment', 'play', 'trò chơi', 'game', 'gaming'],
        'show' => ['program', 'series', 'performance', 'chương trình', 'show', 'buổi diễn'],
        'book' => ['novel', 'literature', 'reading', 'publication', 'sách', 'tiểu thuyết', 'văn học'],
        'tv' => ['television', 'broadcast', 'channel', 'tivi', 'truyền hình', 'kênh'],
        'video' => ['clip', 'recording', 'footage', 'video', 'clip', 'quay phim'],
        'actor' => ['performer', 'star', 'celebrity', 'diễn viên', 'ngôi sao', 'nghệ sĩ'],
        'director' => ['filmmaker', 'creator', 'producer', 'đạo diễn', 'người làm phim'],
        'artist' => ['performer', 'creator', 'musician', 'nghệ sĩ', 'ca sĩ', 'người sáng tạo'],
        'concert' => ['performance', 'show', 'live music', 'hòa nhạc', 'buổi diễn', 'concert'],
        'theater' => ['theatre', 'playhouse', 'stage', 'rạp', 'nhà hát', 'sân khấu'],
        'comedy' => ['humor', 'funny', 'entertainment', 'hài', 'hài kịch', 'vui nhộn'],
        'drama' => ['serious', 'emotional', 'theatrical', 'chính kịch', 'tâm lý', 'cảm động'],
        'action' => ['adventure', 'thrilling', 'exciting', 'hành động', 'phiêu lưu', 'kịch tính'],
        'romance' => ['love story', 'romantic', 'relationship', 'tình cảm', 'lãng mạn', 'yêu đương'],
        'horror' => ['scary', 'frightening', 'thriller', 'kinh dị', 'ma', 'rùng rợn'],
        'documentary' => ['factual', 'educational', 'real', 'tài liệu', 'phim tài liệu', 'giáo dục'],
        'animation' => ['cartoon', 'animated', 'digital', 'hoạt hình', 'phim hoạt hình', 'cartoon'],
        'streaming' => ['online', 'digital', 'internet', 'xem online', 'streaming', 'phát trực tuyến'],
        
        // Vietnamese specific entertainment terms
        'phim' => ['movie', 'film', 'cinema', 'picture'],
        'nhạc' => ['music', 'song', 'melody', 'audio'],
        'ca sĩ' => ['singer', 'vocalist', 'artist', 'performer'],
        'diễn viên' => ['actor', 'actress', 'performer', 'star'],
        'đạo diễn' => ['director', 'filmmaker', 'creator'],
        'rạp phim' => ['cinema', 'movie theater', 'film theater'],
        'phim lẻ' => ['feature film', 'single movie', 'standalone film'],
        'phim bộ' => ['tv series', 'drama series', 'tv show'],
        'phim ngắn' => ['short film', 'short movie', 'brief film'],
        'trailer' => ['movie preview', 'film trailer', 'teaser'],
        'premiere' => ['ra mắt', 'first showing', 'debut'],
        'box office' => ['doanh thu phòng vé', 'ticket sales', 'earnings'],
        'oscar' => ['giải oscar', 'academy award', 'film award'],
        'hollywood' => ['điện ảnh mỹ', 'american cinema', 'us movies'],
        'bollywood' => ['điện ảnh ấn độ', 'indian cinema', 'hindi movies'],
        'k-pop' => ['nhạc hàn', 'korean pop', 'korean music'],
        'v-pop' => ['nhạc việt', 'vietnamese pop', 'vietnam music'],
        'rap' => ['hip hop', 'rap music', 'urban music'],
        'ballad' => ['tình ca', 'love song', 'slow song'],
        'rock' => ['nhạc rock', 'rock music', 'hard music'],
        'pop' => ['nhạc pop', 'popular music', 'mainstream'],
        'jazz' => ['nhạc jazz', 'smooth music', 'blues'],
        'classical' => ['nhạc cổ điển', 'classical music', 'orchestra'],
        'edm' => ['electronic dance music', 'dance music', 'club music'],
        'indie' => ['nhạc độc lập', 'independent music', 'alternative'],
        'acoustic' => ['nhạc acoustic', 'unplugged', 'guitar music'],
        'live show' => ['buổi diễn trực tiếp', 'concert', 'performance'],
        'fanmeeting' => ['gặp gỡ fan', 'fan event', 'meet and greet'],
        'album' => ['đĩa nhạc', 'music collection', 'record'],
        'single' => ['đĩa đơn', 'individual song', 'one track'],
        'mv' => ['music video', 'video clip', 'music clip'],
        'karaoke' => ['hát karaoke', 'singing', 'ktv'],
        'youtube' => ['video platform', 'online video', 'streaming'],
        'tiktok' => ['short video', 'social video', 'viral video'],
        'facebook' => ['social media', 'social network', 'fb'],
        'instagram' => ['photo sharing', 'social platform', 'ig'],
        'netflix' => ['streaming service', 'online movies', 'digital content'],
        'spotify' => ['music streaming', 'online music', 'digital music'],
        'podcast' => ['audio show', 'talk show', 'radio show'],
        'vlog' => ['video blog', 'personal video', 'lifestyle video'],
        'review' => ['đánh giá', 'critique', 'evaluation'],
        'reaction' => ['phản ứng', 'response', 'feedback'],
        'unboxing' => ['mở hộp', 'product reveal', 'unpacking'],
        'gaming' => ['chơi game', 'video gaming', 'esports'],
        'livestream' => ['phát trực tiếp', 'live broadcast', 'real time'],
        'vtuber' => ['virtual youtuber', 'anime streamer', 'virtual character'],
        'cosplay' => ['hóa trang', 'costume play', 'character dress up'],
        'anime' => ['hoạt hình nhật', 'japanese animation', 'manga'],
        'manga' => ['truyện tranh nhật', 'japanese comic', 'graphic novel'],
        'webtoon' => ['truyện tranh web', 'digital comic', 'online comic'],
        'novel' => ['tiểu thuyết', 'fiction book', 'story book'],
        'light novel' => ['tiểu thuyết nhẹ', 'illustrated novel', 'ln'],
        'fanfiction' => ['truyện fan', 'fan story', 'derivative work'],
        'meme' => ['ảnh chế', 'funny image', 'viral content'],
        'viral' => ['lan truyền', 'trending', 'popular'],
        'trending' => ['thịnh hành', 'popular', 'hot topic'],
        'hashtag' => ['thẻ bài', 'tag', 'keyword'],
        'influencer' => ['người có ảnh hưởng', 'content creator', 'social media star'],
        'kol' => ['key opinion leader', 'influencer', 'brand ambassador'],
        'content creator' => ['người sáng tạo nội dung', 'creator', 'digital artist'],
        'subscription' => ['đăng ký', 'follow', 'subscribe'],
        'like' => ['thích', 'heart', 'upvote'],
        'share' => ['chia sẻ', 'spread', 'repost'],
        'comment' => ['bình luận', 'feedback', 'response'],
        'follow' => ['theo dõi', 'subscribe', 'track'],
        'unfollow' => ['bỏ theo dõi', 'unsubscribe', 'stop following'],
        'block' => ['chặn', 'ban', 'restrict'],
        'report' => ['báo cáo', 'flag', 'complain'],
        'premium' => ['cao cấp', 'paid version', 'upgraded'],
        'free' => ['miễn phí', 'no cost', 'gratis'],
        'trial' => ['dùng thử', 'test period', 'demo'],
        'download' => ['tải xuống', 'get', 'save'],
        'offline' => ['ngoại tuyến', 'no internet', 'disconnected'],
        'online' => ['trực tuyến', 'connected', 'internet'],
        'hd' => ['high definition', 'chất lượng cao', 'clear'],
        '4k' => ['ultra hd', 'highest quality', 'crystal clear'],
        'subtitle' => ['phụ đề', 'captions', 'text overlay'],
        'dubbed' => ['lồng tiếng', 'voice over', 'translated audio'],
    ];
}

/**
 * Check if predefined dictionaries feature is active
 *
 * @return bool True if at least one dictionary is selected
 */
function init_plugin_suite_live_search_is_predefined_active() {
    $selected_dictionaries = get_option(INIT_PLUGIN_SUITE_LS_PREDEFINED_DICT_OPTION, []);
    return !empty($selected_dictionaries) && is_array($selected_dictionaries);
}

/**
 * Get count of active dictionaries
 *
 * @return int Number of active dictionaries
 */
function init_plugin_suite_live_search_get_active_dict_count() {
    $selected_dictionaries = get_option(INIT_PLUGIN_SUITE_LS_PREDEFINED_DICT_OPTION, []);
    return is_array($selected_dictionaries) ? count($selected_dictionaries) : 0;
}
