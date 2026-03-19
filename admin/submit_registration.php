<?php
session_start();

// Polyfill random_bytes for older PHP
if (!function_exists('random_bytes') && function_exists('openssl_random_pseudo_bytes')) {
    function random_bytes($length) { return openssl_random_pseudo_bytes($length); }
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once __DIR__ . '/../includes/storage.php';

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone number (basic validation)
function validatePhone($phone) {
    return preg_match('/^[\+]?[0-9\s\-\(\)]{10,20}$/', $phone);
}

// Response function
function sendResponse($success, $message, $data = null, $errors = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'errors' => array_values(array_filter($errors, function ($value) {
            return is_string($value) && trim($value) !== '';
        }))
    ]);
    exit;
}

// Function to get translated KingsChat message
function getTranslatedKingsChatMessage($name, $regDate, $language) {
    $messages = [
        'en' => "Dear {$name}\n\n" .
                "Thank you for registering for **Rhapathon 2026** with **Pastor Chris**, taking place from Monday 4th to Friday 8th May 2026. We are delighted to have you on board.\n\n" .
                "Join us to **sponsor Rhapsody of Realities** as we prepare for this upcoming program.\n\n" .
                "🔥 **Be a Rhapsody Wonder Today**!!\n" .
                "Sponsor at least **one copy** in each of the **8,123 languages** and **over 4,000 dialects**, and be part of taking God's Word to the nations. Every copy you sponsor is a seed of salvation, healing, and transformation.\n\n" .
                "👉 You can give daily through **your zone**\n" .
                "or alternatively using this link: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "Together, we are changing the world and preparing the nations for the return of our Lord Jesus Christ!\n\n" .
                "**Rhapathon 2026 Team**",

        'es' => "Estimado {$name}\n\n" .
                "Gracias por registrarte para **Rhapathon 2026** con **Pastor Chris**, que tendrá lugar del lunes 4 al viernes 8 de mayo de 2026. Estamos encantados de tenerte a bordo.\n\n" .
                "Únete a nosotros para **patrocinar Rhapsody of Realities** mientras nos preparamos para este próximo programa.\n\n" .
                "🔥 ¡**Sé una Maravilla de Rhapsody Hoy**!!\n" .
                "Patrocina al menos **una copia** en cada uno de los **8,123 idiomas** y **más de 4,000 dialectos**, y sé parte de llevar la Palabra de Dios a las naciones. Cada copia que patrocinas es una semilla de salvación, sanidad y transformación.\n\n" .
                "👉 Puedes dar diariamente a través de **tu zona**\n" .
                "o alternativamente usando este enlace: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "Juntos, estamos cambiando el mundo y preparando a las naciones para el regreso de nuestro Señor Jesucristo!\n\n" .
                "**Equipo de Rhapathon 2026**",

        'fr' => "Cher {$name}\n\n" .
                "Merci de vous être inscrit pour **Rhapathon 2026** avec **Pasteur Chris**, qui se déroulera du lundi 4 au vendredi 8 mai 2026. Nous sommes ravis de vous avoir à bord.\n\n" .
                "Rejoignez-nous pour **parrainer Rhapsody of Realities** alors que nous nous préparons pour ce prochain programme.\n\n" .
                "🔥 **Soyez une Merveille de Rhapsody Aujourd'hui**!!\n" .
                "Parrainez au moins **une copie** dans chacun des **8,123 langues** et **plus de 4,000 dialectes**, et faites partie de la diffusion de la Parole de Dieu aux nations. Chaque copie que vous parrainez est une semence de salut, de guérison et de transformation.\n\n" .
                "👉 Vous pouvez donner quotidiennement via **votre zone**\n" .
                "ou alternativement en utilisant ce lien: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "Ensemble, nous changeons le monde et préparons les nations pour le retour de notre Seigneur Jésus-Christ!\n\n" .
                "**Équipe Rhapathon 2026**",

        'pt' => "Prezado {$name}\n\n" .
                "Obrigado por se registrar para o **Rhapathon 2026** com **Pastor Chris**, que acontecerá de segunda-feira, 4, a sexta-feira, 8 de maio de 2026. Estamos encantados em tê-lo conosco.\n\n" .
                "Junte-se a nós para **patrocinar Rhapsody of Realities** enquanto nos preparamos para este próximo programa.\n\n" .
                "🔥 **Seja uma Maravilha da Rhapsody Hoje**!!\n" .
                "Patrocine pelo menos **uma cópia** em cada uma das **8,123 línguas** e **mais de 4,000 dialetos**, e faça parte de levar a Palavra de Deus às nações. Cada cópia que você patrocina é uma semente de salvação, cura e transformação.\n\n" .
                "👉 Você pode doar diariamente através da **sua zona**\n" .
                "ou alternativamente usando este link: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "Juntos, estamos mudando o mundo e preparando as nações para o retorno de nosso Senhor Jesus Cristo!\n\n" .
                "**Equipe Rhapathon 2026**",

        'de' => "Liebes {$name}\n\n" .
                "Vielen Dank für Ihre Anmeldung zum **Rhapathon 2026** mit **Pastor Chris**, der vom Montag, 4. bis Freitag, 8. Mai 2026 stattfindet. Wir freuen uns, Sie an Bord zu haben.\n\n" .
                "Begleiten Sie uns beim **Sponsern von Rhapsody of Realities**, während wir uns auf dieses kommende Programm vorbereiten.\n\n" .
                "🔥 **Seien Sie heute ein Rhapsody-Wunder**!!\n" .
                "Sponsern Sie mindestens **eine Kopie** in jeder der **8,123 Sprachen** und **über 4,000 Dialekte**, und seien Sie Teil der Verbreitung des Wortes Gottes in die Nationen. Jede Kopie, die Sie sponsern, ist ein Samen der Erlösung, Heilung und Veränderung.\n\n" .
                "👉 Sie können täglich über **Ihre Zone** spenden\n" .
                "oder alternativ über diesen Link: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "Gemeinsam verändern wir die Welt und bereiten die Nationen auf die Rückkehr unseres Herrn Jesus Christus vor!\n\n" .
                "**Rhapathon 2026 Team**",

        'zh' => "{$name}您好\n\n" .
                "感谢您注册参加**Rhapathon 2026** with **Pastor Chris**，该活动将于2026年5月4日至8日举行。我们很高兴您加入我们。\n\n" .
                "加入我们一起**赞助 Rhapsody of Realities**，为即将到来的节目做准备。\n\n" .
                "🔥 **Today be a Rhapsody Wonder**!!\n" .
                "赞助至少**一本**在**8,123种语言**和**超过4,000种方言**中的每一本，并成为将上帝的话语传给万国的一部分。您赞助的每一本都是救恩、医治和转变的种子。\n\n" .
                "👉 您可以通过**您的区域**每天捐款\n" .
                "或者使用此链接: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "让我们一起改变世界，为我们主耶稣基督的再来预备万国！\n\n" .
                "**Rhapathon 2026团队**",

        'ar' => "عزيزي {$name}\n\n" .
                "شكراً لتسجيلك في **Rhapathon 2026** with **Pastor Chris**، الذي سيقام من الاثنين 4 إلى الجمعة 8 مايو 2026. نحن سعداء بانضمامك إلينا.\n\n" .
                "انضم إلينا في **رعاية Rhapsody of Realities** ونحن نستعد لهذا البرنامج القادم.\n\n" .
                "🔥 **كن عجائب رhapsody اليوم**!!\n" .
                "رعاية ما لا يقل عن **نسخة واحدة** في كل من **8,123 لغة** و**أكثر من 4,000 لهجة**، وكن جزءاً من حمل كلمة الله إلى الأمم. كل نسخة ترعاها هي بذرة خلاص وشفاء وتحول.\n\n" .
                "👉 يمكنك التبرع يومياً من خلال **منطقتك**\n" .
                "أو بديلاً استخدم هذا الرابط: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "معاً، نحن نغير العالم ونعد الأمم لعودة ربنا يسوع المسيح!\n\n" .
                "**فريق Rhapathon 2026**",

        'ru' => "Дорогой {$name}\n\n" .
                "Спасибо за регистрацию на **Rhapathon 2026** с **Пастором Chris**, который пройдет с понедельника 4 по пятницу 8 мая 2026 года. Мы рады приветствовать вас на борту.\n\n" .
                "Присоединяйтесь к нам в **спонсорстве Rhapsody of Realities**, пока мы готовимся к этой предстоящей программе.\n\n" .
                "🔥 **Станьте чудом Rhapsody сегодня**!!\n" .
                "Спонсируйте хотя бы **одну копию** в каждом из **8,123 языков** и **более 4,000 диалектов**, и станьте частью распространения Слова Божьего народам. Каждая копия, которую вы спонсируете, является семенем спасения, исцеления и преображения.\n\n" .
                "👉 Вы можете жертвовать ежедневно через **свою зону**\n" .
                "или альтернативно используя эту ссылку: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "Вместе мы меняем мир и готовим народы к возвращению нашего Господа Иисуса Христа!\n\n" .
                "**Команда Rhapathon 2026**",

        'ja' => "{$name}様\n\n" .
                "**Rhapathon 2026** with **Pastor Chris**へのご登録ありがとうございます。2026年5月4日（月）から8日（金）まで開催されます。ご参加いただけることを大変嬉しく思います。\n\n" .
                "この次のプログラムの準備をしながら、**スポンサー Rhapsody of Realities**にご参加ください。\n\n" .
                "🔥 **Today be a Rhapsody Wonder**!!\n" .
                "**8,123言語**と**4,000以上のダイアレクト**のそれぞれで少なくとも**1冊**を**スポンサー**し、神の言葉を国々に届ける一部となっていただきます。あなたが**スポンサー**するすべての**コピー**は、救い、癒し、変革の種となります。\n\n" .
                "👉 **あなたのゾーン**を通じて毎日ご献金いただけます\n" .
                "またはこちらのリンクをご利用ください：https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "私たちは共に世界を変え、私たちの主イエス・キリストの再臨のために国々を準備します！\n\n" .
                "**Rhapathon 2026チーム**",

        'hi' => "प्रिय {$name}\n\n" .
                "**Rhapathon 2026** with **Pastor Chris** के लिए पंजीकरण करने के लिए धन्यवाद, जो 4 से 8 मई 2026 तक आयोजित किया जाएगा। हम आपको हमारे साथ होने पर प्रसन्न हैं।\n\n" .
                "हमारे अगले कार्यक्रम की तैयारी करते हुए, **स्पॉन्सर Rhapsody of Realities** में हमारे साथ जुड़ें।\n\n" .
                "🔥 **Today be a Rhapsody Wonder**!!\n" .
                "**8,123 भाषाओं** और **4,000 से अधिक डायलेक्ट्स** में से प्रत्येक में कम से कम **एक कॉपी** का **स्पॉन्सर** करें और राष्ट्रों तक भगवान के वचन को पहुंचाने का हिस्सा बनें। आपके द्वारा **स्पॉन्सर** की गई प्रत्येक **कॉपी** मुक्ति, चंगाई और परिवर्तन का बीज है।\n\n" .
                "👉 आप **अपनी ज़ोन** के माध्यम से प्रतिदिन दान कर सकते हैं\n" .
                "या वैकल्पिक रूप से इस लिंक का उपयोग करें: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "हम साथ मिलकर दुनिया को बदल रहे हैं और हमारे प्रभु यीशु मसीह के वापसी के लिए राष्ट्रों को तैयार कर रहे हैं!\n\n" .
                "**Rhapathon 2026 टीम**",

        'it' => "Caro {$name}\n\n" .
                "Grazie per esserti registrato per **Rhapathon 2026** with **Pastor Chris**, che si terrà da lunedì 4 a venerdì 8 maggio 2026. Siamo felici di averti a bordo.\n\n" .
                "Unisciti a noi per **sponsorizzare Rhapsody of Realities** mentre ci prepariamo per questo prossimo programma.\n\n" .
                "🔥 **Sii una Meraviglia di Rhapsody Oggi**!!\n" .
                "**Sponsorizza almeno una copia** in ciascuna delle **8,123 lingue** e **oltre 4,000 dialetti**, e fai parte del portare la Parola di Dio alle nazioni. Ogni copia che sponsorizzi è un seme di salvezza, guarigione e trasformazione.\n\n" .
                "👉 Puoi donare giornalmente attraverso **la tua zona**\n" .
                "o in alternativa utilizzando questo link: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "Insieme stiamo cambiando il mondo e preparando le nazioni per il ritorno del nostro Signore Gesù Cristo!\n\n" .
                "**Team Rhapathon 2026**",

        'ko' => "{$name}님\n\n" .
                "**Rhapathon 2026** with **Pastor Chris**에 등록해 주셔서 감사합니다. 2026년 5월 4일(월)부터 8일(금)까지 진행됩니다. 함께 하게 되어 기쁩니다.\n\n" .
                "다음 프로그램을 준비하면서 **스폰서 Rhapsody of Realities**에 함께 참여해 주세요.\n\n" .
                "🔥 **Today be a Rhapsody Wonder**!!\n" .
                "**8,123 언어**와 **4,000 이상의 방언** 각각에서 최소 **한 권**을 **스폰서**하고 하나님의 말씀을 민족들에게 전하는 일부가 되십시오. 귀하가 **스폰서**하는 각 **복사본**은 구원, 치유, 변화의 씨앗입니다.\n\n" .
                "👉 **귀하의 구역**을 통해 매일 기부하실 수 있습니다\n" .
                "또는 이 링크를 사용하십시오: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "우리는 함께 세상을 변화시키고 우리 주 예수 그리스도의 재림을 위해 민족들을 준비하고 있습니다!\n\n" .
                "**Rhapathon 2026 팀**",

        'tr' => "Sayın {$name}\n\n" .
                "**Rhapathon 2026** with **Pastor Chris** için kayıt olduğunuz için teşekkür ederiz. 4-8 Mayıs 2026 tarihleri arasında gerçekleşecek. Sizinle birlikte olmaktan mutluyuz.\n\n" .
                "Bu sonraki program için hazırlanırken **sponsor Rhapsody of Realities**'e katılın.\n\n" .
                "🔥 **Bugün bir Rhapsody Harikası ol**!!\n" .
                "**8,123 dil** ve **4,000'den fazla lehçe**'nin her birinde en az **bir kopya sponsor** olun ve Tanrı'nın Sözünü uluslara ulaştırmanın bir parçası olun. **Sponsor** olduğunuz her **kopya**, kurtuluş, şifa ve dönüşümün bir tohumudur.\n\n" .
                "👉 **Bölgeniz** aracılığıyla her gün bağış yapabilirsiniz\n" .
                "veya alternatif olarak bu bağlantıyı kullanın: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "Birlikte dünyayı değiştiriyor ve ulusları Rabbimiz İsa Mesih'in dönüşü için hazırlıyoruz!\n\n" .
                "**Rhapathon 2026 Takımı**",

        'th' => "เรียน {$name}\n\n" .
                "ขอบคุณที่ลงทะเบียนเข้าร่วม **Rhapathon 2026** with **Pastor Chris** ซึ่งจะจัดขึ้นระหว่างวันที่ 4-8 พฤษภาคม 2026 เรายินดีเป็นอย่างยิ่งที่ได้มีคุณร่วมด้วย\n\n" .
                "ร่วมกับเราในการ **สนับสนุน Rhapsody of Realities** ขณะที่เรากำลังเตรียมตัวสำหรับโปรแกรมต่อไปนี้\n\n" .
                "🔥 **Today be a Rhapsody Wonder**!!\n" .
                "**สนับสนุนอย่างน้อยหนึ่งเล่ม** ในแต่ละภาษาจาก **8,123 ภาษา**และ **มากกว่า 4,000 ภาษาถิ่น** และเป็นส่วนหนึ่งในการนำพระวจนะของพระเจ้าไปสู่อาณาจักรทุกแห่ง **หนังสือเล่ม**ที่คุณ **สนับสนุน** คือเมล็ดพันธุ์แห่งความรอด การรักษา และการเปลี่ยนแปลง\n\n" .
                "👉 คุณสามารถบริจาคทุกวันผ่าน **โซน ของคุณ**\n" .
                "หรือเลือกใช้ลิงค์นี้: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "ด้วยกันเรากำลังเปลี่ยนโลกและเตรียมประชาชาติสำหรับการกลับมาขององค์พระผู้เป็นเจ้าของเรา พระเยซูคริสต์!\n\n" .
                "**ทีม Rhapathon 2026**",

        'vi' => "Kính gửi {$name}\n\n" .
                "Cảm ơn bạn đã đăng ký tham gia **Rhapathon 2026** with **Pastor Chris**, sẽ diễn ra từ thứ Hai 4 đến thứ Sáu 8 tháng 5 năm 2026. Chúng tôi rất vui mừng được có bạn cùng tham gia.\n\n" .
                "Hãy tham gia với chúng tôi trong việc **tài trợ Rhapsody of Realities** trong khi chúng tôi chuẩn bị cho chương trình sắp tới.\n\n" .
                "🔥 **Hôm Nay Hãy Là Một Phép Lạ Của Rhapsody**!!\n" .
                "**Tài trợ ít nhất một bản** trong từng ngôn ngữ **8,123** và **hơn 4,000 phương ngữ** và trở thành phần của việc mang Lời Phàm của Chúa đến các quốc gia. Mỗi **bản sao** bạn **tài trợ** là một hạt giống của sự cứu rỗi, chữa lành và biến đổi.\n\n" .
                "👉 Bạn có thể quyên góp hàng ngày thông qua **vùng của bạn**\n" .
                "hoặc sử dụng liên kết này: https://give.rhapsodyofrealities.org/ref/official\n\n" .
                "Cùng nhau chúng ta đang thay đổi thế giới và chuẩn bị cho các quốc gia về sự trở lại của Chúa Giêsu Kitô!\n\n" .
                "**Nhóm Rhapathon 2026**"
    ];

    // Return English as default if language not found
    return $messages[$language] ?? $messages['en'];
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

// Validate CSRF token (session or double-submit cookie fallback)
$postToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';
$cookieToken = $_COOKIE['csrf_token'] ?? '';

$validCsrf = false;
if ($postToken !== '') {
    if ($sessionToken !== '' && hash_equals($sessionToken, $postToken)) {
        $validCsrf = true;
    } elseif ($cookieToken !== '' && hash_equals($cookieToken, $postToken)) {
        // Session might have rotated or expired; accept cookie token and restore session token
        $_SESSION['csrf_token'] = $cookieToken;
        $validCsrf = true;
    }
}

if (!$validCsrf) {
    header('X-CSRF-Status: invalid');
    echo json_encode([
        'success' => false,
        'code' => 'csrf_invalid',
        'message' => 'Your session expired or the page was open too long. Click OK to reload safely — your inputs will be restored.'
    ]);
    exit;
}

// Required fields validation
$required_fields = [
    'title', 'first_name', 'last_name', 'email', 'phone',
    'onsite_participation', 'days_validation', 'preferred_language', 'affiliation_type'
];

$errors = [];
$sanitized_data = [];

// Validate and sanitize each required field
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $errors[] = "Field '$field' is required";
    } else {
        $sanitized_data[$field] = sanitizeInput($_POST[$field]);
    }
}

// Validate optional fields
$optional_fields = ['kingschat_username', 'online_participation', 'feedback'];
foreach ($optional_fields as $field) {
    if (isset($_POST[$field]) && !empty(trim($_POST[$field]))) {
        $value = sanitizeInput($_POST[$field]);

        // Special handling for KingsChat username - normalize with @ prefix
        if ($field === 'kingschat_username' && !str_starts_with($value, '@')) {
            $value = '@' . $value;
        }

        $sanitized_data[$field] = $value;
    }
}

// Check for duplicate registrations
if (!empty($sanitized_data['email'])) {
    $duplicates = registration_storage_duplicate_status(
        (string) $sanitized_data['email'],
        (string) ($sanitized_data['kingschat_username'] ?? '')
    );

    if (!empty($duplicates['email'])) {
        $errors[] = 'This email address has already been registered. If this is your email, please contact support on KingsChat: @kingsblast, or use a different email address.';
    }

    if (!empty($duplicates['kingschat'])) {
        $errors[] = 'This KingsChat username has already been registered. Please use a different KingsChat username or contact support on KingsChat: @kingsblast.';
    }
}

// Process selected days array
if (isset($_POST['selected_days']) && is_array($_POST['selected_days'])) {
    $sanitized_data['selected_days'] = array_map('sanitizeInput', $_POST['selected_days']);
} else {
    $sanitized_data['selected_days'] = [];
}

// Process session selections for each day
$sanitized_data['sessions'] = [];
$days_with_sessions = ['tuesday', 'wednesday', 'thursday', 'friday'];
foreach ($days_with_sessions as $day) {
    $session_key = $day . '_sessions';
    if (isset($_POST[$session_key]) && is_array($_POST[$session_key])) {
        $sanitized_data['sessions'][$day] = array_map('sanitizeInput', $_POST[$session_key]);
    } else {
        $sanitized_data['sessions'][$day] = [];
    }
}


// Additional validation
if (!empty($sanitized_data['email']) && !validateEmail($sanitized_data['email'])) {
    $errors[] = 'Invalid email format';
}

if (!empty($sanitized_data['phone']) && !validatePhone($sanitized_data['phone'])) {
    $errors[] = 'Invalid phone number format';
}

// Validate that at least one day is selected
if (empty($sanitized_data['selected_days'])) {
    $errors[] = 'Please select at least one day to attend';
}

// Validate sessions if onsite participation is selected
if (isset($sanitized_data['onsite_participation']) && $sanitized_data['onsite_participation'] === 'yes') {
    $days_with_sessions = ['tuesday', 'wednesday', 'thursday', 'friday'];
    foreach ($sanitized_data['selected_days'] as $selected_day) {
        if (in_array($selected_day, $days_with_sessions)) {
            if (empty($sanitized_data['sessions'][$selected_day])) {
                $errors[] = "Please select at least one session for " . ucfirst($selected_day);
            }
        }
    }
}


// Validate church/network information based on explicit selection
$affiliationType = $sanitized_data['affiliation_type'] ?? '';
$hasNetwork = isset($_POST['network']) && !empty(trim($_POST['network']));
$hasZone = isset($_POST['zone']) && !empty(trim($_POST['zone']));
$hasGroup = isset($_POST['group']) && !empty(trim($_POST['group']));
$hasChurch = isset($_POST['church']) && !empty(trim($_POST['church']));

if ($affiliationType !== 'church' && $affiliationType !== 'network') {
    $errors[] = 'Please choose either church or network';
}

if ($affiliationType === 'church' && !$hasZone) {
    $errors[] = 'Please select your zone';
}

if ($affiliationType === 'network' && !$hasNetwork) {
    $errors[] = 'Please select your network';
}

// Handle optional church fields
if ($hasZone) {
    $sanitized_data['zone'] = sanitizeInput($_POST['zone']);
}
if ($hasGroup) {
    $sanitized_data['group'] = sanitizeInput($_POST['group']);
}
if ($hasChurch) {
    $sanitized_data['church'] = sanitizeInput($_POST['church']);
}

// Handle network field
if ($hasNetwork) {
    $sanitized_data['network'] = sanitizeInput($_POST['network']);

    // Validate manual network field if network is 'OTHER'
    if ($sanitized_data['network'] === 'OTHER') {
        if (!isset($_POST['manual_network']) || empty(trim($_POST['manual_network']))) {
            $errors[] = 'Please specify your network when selecting OTHER';
        } else {
            $sanitized_data['manual_network'] = sanitizeInput($_POST['manual_network']);
        }
    }
}

// Check for validation errors
if (!empty($errors)) {
    sendResponse(false, 'Please correct the highlighted registration issues and try again.', null, $errors);
}

// Prepare data for storage
$registration_data = [
    'id' => uniqid('reg_', true),
    'timestamp' => date('Y-m-d H:i:s'),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
    'personal_info' => [
        'title' => $sanitized_data['title'],
        'first_name' => $sanitized_data['first_name'],
        'last_name' => $sanitized_data['last_name'],
        'email' => $sanitized_data['email'],
        'phone' => $sanitized_data['phone'],
        'kingschat_username' => $sanitized_data['kingschat_username'] ?? ''
    ],
    'church_info' => [
        'affiliation_type' => $affiliationType,
        'zone' => $sanitized_data['zone'] ?? '',
        'network' => $sanitized_data['network'] ?? '',
        'manual_network' => $sanitized_data['manual_network'] ?? '',
        'group' => $sanitized_data['group'] ?? '',
        'church' => $sanitized_data['church'] ?? ''
    ],
    'event_info' => [
        'selected_days' => $sanitized_data['selected_days'],
        'sessions' => $sanitized_data['sessions'],
        'onsite_participation' => $sanitized_data['onsite_participation'],
        'online_participation' => $sanitized_data['online_participation'] ?? ''
    ],
    'additional_info' => [
        'feedback' => $sanitized_data['feedback'] ?? ''
    ],
    'language_preference' => $sanitized_data['preferred_language']
];

try {
    registration_storage_insert($registration_data);
} catch (Throwable $e) {
    sendResponse(false, 'Unable to save registration data: ' . $e->getMessage());
}

// Try to send KingsChat notification to the registrant (best-effort)
try {
    // Only attempt if a KingsChat username was provided
    $kcUsername = trim($registration_data['personal_info']['kingschat_username'] ?? '');
    if ($kcUsername !== '') {
        // Load KingsChat helpers
        require_once __DIR__ . '/../kingschat/helpers.php';
        // Optionally load token refresh utilities
        @require_once __DIR__ . '/../kingschat/token_refresh.php';

        // Attempt to restore a persistent token from config if none in session and not using remote mode
        if (!kc_remote_enabled() && !kc_is_authenticated()) {
            $cfg = __DIR__ . '/../secure_data/kc_config.json';
            if (is_file($cfg)) {
                $cfgJson = json_decode(@file_get_contents($cfg), true);
                if (is_array($cfgJson) && !empty($cfgJson['access_token'])) {
                    $exp = (int)($cfgJson['expires_at'] ?? 0);
                    if ($exp === 0 || $exp > time()) {
                        $_SESSION['kc_access_token'] = $cfgJson['access_token'];
                        if (!empty($cfgJson['refresh_token'])) {
                            $_SESSION['kc_refresh_token'] = $cfgJson['refresh_token'];
                        }
                    }
                }
            }
        }

        // Refresh token if helper is available
        if (function_exists('ensureValidToken')) {
            @ensureValidToken(300);
        }

        // Proceed only if we have an auth path (session token or remote mode)
        $append_outbox = function(array $entry) {
            outbox_storage_append($entry);
        };

        if (kc_remote_enabled() || kc_is_authenticated()) {
            // Refresh token immediately before sending message to ensure it's fresh
            if (function_exists('ensureValidToken')) {
                $tokenRefreshed = @ensureValidToken(120); // Refresh if expires within 2 minutes
                if (!$tokenRefreshed) {
                    error_log('Failed to refresh KingsChat token for registration confirmation to ' . $kcUsername);
                }
            }

            $userId = kc_lookup_user_id($kcUsername);
            if ($userId) {
                $title = trim($registration_data['personal_info']['title'] ?? '');
                $first = trim($registration_data['personal_info']['first_name'] ?? '');
                $last  = trim($registration_data['personal_info']['last_name'] ?? '');
                $nameCore = trim($first . ' ' . $last);
                $name = trim(($title !== '' ? ($title . ' ') : '') . $nameCore);
                $regDate = date('M j, Y');
                $msg = getTranslatedKingsChatMessage($name, $regDate, $sanitized_data['preferred_language']);
                $res = @kc_send_text_message($userId, $msg);
                $ok = is_array($res) ? ($res['ok'] ?? false) : false;
                $thisEntry = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'origin' => 'auto_registration',
                    'registration_id' => $registration_data['id'],
                    'username' => ltrim($kcUsername, '@'),
                    'name' => $name,
                    'message' => $msg,
                    'status' => $ok ? 'sent' : 'failed',
                ];
                if (!$ok) { $thisEntry['error'] = $res['error'] ?? ('HTTP ' . ($res['status'] ?? 0)); }
                $append_outbox($thisEntry);
            } else {
                $append_outbox([
                    'timestamp' => date('Y-m-d H:i:s'),
                    'origin' => 'auto_registration',
                    'registration_id' => $registration_data['id'],
                    'username' => ltrim($kcUsername, '@'),
                    'status' => 'lookup_failed'
                ]);
            }
        } else {
            $append_outbox([
                'timestamp' => date('Y-m-d H:i:s'),
                'origin' => 'auto_registration',
                'registration_id' => $registration_data['id'],
                'username' => ltrim($kcUsername, '@'),
                'status' => 'unauthenticated'
            ]);
        }
    }
} catch (Throwable $e) {
    // Do not block registration on notification errors; log quietly
    error_log('KC notify failed: ' . $e->getMessage());
}

// Generate new CSRF token for next request
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Success response
sendResponse(true, 'Registration submitted successfully!', [
    'registration_id' => $registration_data['id'],
    'selected_days' => $sanitized_data['selected_days'],
    'name' => $sanitized_data['first_name'] . ' ' . $sanitized_data['last_name']
]);
?> 
