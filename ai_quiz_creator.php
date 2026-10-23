<?php
require_once __DIR__ . '/security.php';

initSecureSession();
setSecurityHeaders();

requireLogin();

$db = db();

// Gemini API configuration
define('GEMINI_API_KEY', $_ENV['api'] ?? '');

// Try different Gemini models in order of preference (Gemini 1.5 models)
$available_models = [
    'gemini-1.5-flash',
    'gemini-1.5-pro'
];

$selected_model = 'gemini-1.5-flash'; // Default, will be tested

// Try both v1 and v1beta API versions
$api_versions = ['v1', 'v1beta'];
$api_version = 'v1'; // Default

define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/' . $api_version . '/models/' . $selected_model . ':generateContent?key=' . GEMINI_API_URL);

// Function to test API availability and get the correct endpoint
function find_working_gemini_api(): array|false {
    global $available_models, $debug_logs;
    $api_versions = ['v1beta', 'v1']; // Try v1beta first as it's more commonly supported
    
    $debug_logs[] = "Testing API availability with " . count($available_models) . " models...";
    
    foreach ($available_models as $model) {
        foreach ($api_versions as $version) {
            $test_url = 'https://generativelanguage.googleapis.com/' . $version . '/models/' . $model . ':generateContent?key=' . GEMINI_API_KEY;
            $debug_logs[] = "Testing: $model with API $version";
            
            // Simple test request
            $test_data = [
                'contents' => [[
                    'parts' => [[
                        'text' => 'Hello'
                    ]]
                ]]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $test_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $result = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            $debug_logs[] = "Result: HTTP $http_code" . ($curl_error ? " Error: $curl_error" : "");
            
            if ($result !== false && ($http_code == 200 || $http_code == 201)) {
                $debug_logs[] = "✅ Found working API: $model with $version";
                return ['model' => $model, 'version' => $version];
            } elseif ($http_code == 400) {
                $response_data = json_decode($result, true);
                if (isset($response_data['error']['message'])) {
                    $debug_logs[] = "API Error: " . $response_data['error']['message'];
                }
            }
        }
    }
    
    $debug_logs[] = "❌ No working API endpoints found";
    return false;
}

$error_message = '';
$success_message = '';
$processing = false;
$debug_mode = isset($_GET['debug']) && $_GET['debug'] === '1';
$debug_logs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['pdf_file']) || isset($_POST['test_mode']))) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        logSecurityEvent('csrf_validation_failed', "AI quiz creation attempt", 'warning');
    } else {
        $processing = true;
        $is_test_mode = isset($_POST['test_mode']);
        
        try {
            $debug_logs[] = "Starting AI quiz generation process...";
            $debug_logs[] = "Test mode: " . ($is_test_mode ? "Yes" : "No");
            
            // Validate file upload (skip for test mode)
            if (!$is_test_mode) {
                if ($_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('File upload failed. Please try again.');
                }
                
                // Validate file using security helper
                $fileErrors = validateFileUpload($_FILES['pdf_file'], ['pdf'], 10 * 1024 * 1024);
                if (!empty($fileErrors)) {
                    throw new Exception(implode(' ', $fileErrors));
                }
            }
            
            $quiz_title = sanitizeString($_POST['quiz_title'] ?? '', 255);
            $num_questions = sanitizeInt($_POST['num_questions'] ?? 10, 3, 20);
            $difficulty = sanitizeString($_POST['difficulty'] ?? 'medium', 20);
            
            if (empty($quiz_title)) {
                throw new Exception('Please provide a quiz title.');
            }
            
            if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
                throw new Exception('Invalid difficulty level.');
            }
            
            $debug_logs[] = "Validation passed. Title: $quiz_title, Questions: $num_questions, Difficulty: $difficulty";
            
            if ($is_test_mode) {
                // Use sample content for testing
                $pdf_text = "Document: Test Biology Content\n";
                $pdf_text .= "Title: Introduction to Cell Biology\n";
                $pdf_text .= "Subject: Biology\n";
                $pdf_text .= "Content: Cell biology is the study of cell structure and function, and it revolves around the concept that the cell is the fundamental unit of life. Most cells are microscopic and are the basic structural and functional units of all living organisms. Cells contain various organelles including the nucleus, mitochondria, endoplasmic reticulum, and ribosomes. The cell membrane controls what enters and exits the cell. Photosynthesis occurs in chloroplasts in plant cells, converting light energy into chemical energy. DNA contains genetic information and is stored in the nucleus of eukaryotic cells.";
                
                $debug_logs[] = "Using test mode with sample biology content. Length: " . strlen($pdf_text) . " characters";
            } else {
                // Extract PDF content
                require_once 'pdf_extractor.php';
                
                $pdf_text = SimplePDFExtractor::extractText($_FILES['pdf_file']['tmp_name']);
                $pdf_info = SimplePDFExtractor::getPDFInfo($_FILES['pdf_file']['tmp_name']);
                
                // Debug PDF extraction
                if (empty($pdf_text) || strlen($pdf_text) < 10) {
                    throw new Exception('Failed to extract meaningful content from PDF. PDF text length: ' . strlen($pdf_text) . ' | PDF info: ' . json_encode($pdf_info));
                }
                
                // Enhanced content for better AI understanding
                $enhanced_content = "Document: " . basename($_FILES['pdf_file']['name']) . "\n";
                if (isset($pdf_info['title']) && !empty($pdf_info['title'])) {
                    $enhanced_content .= "Title: " . $pdf_info['title'] . "\n";
                }
                if (isset($pdf_info['subject']) && !empty($pdf_info['subject'])) {
                    $enhanced_content .= "Subject: " . $pdf_info['subject'] . "\n";
                }
                $enhanced_content .= "Content: " . $pdf_text;
                
                $pdf_text = $enhanced_content;
                
                // Debug final content
                if (strlen($pdf_text) > 10000) {
                    $pdf_text = substr($pdf_text, 0, 8000) . "\n\n[Content truncated for API limits]";
                }
                
                $debug_logs[] = "PDF content extracted successfully. Length: " . strlen($pdf_text) . " characters";
            }
            
            // Prepare Gemini API request
            $prompt = "Based on the following document content, create $num_questions multiple choice quiz questions with difficulty level: $difficulty.\n\nDocument Content:\n$pdf_text\n\nPlease generate exactly $num_questions quiz questions in the following JSON format:\n{\n  \"questions\": [\n    {\n      \"question\": \"Question text here?\",\n      \"option_a\": \"First option\",\n      \"option_b\": \"Second option\", \n      \"option_c\": \"Third option\",\n      \"option_d\": \"Fourth option\",\n      \"correct_option\": \"A\"\n    }\n  ]\n}\n\nRequirements:\n- Questions should be relevant to the document content\n- Each question must have exactly 4 options (A, B, C, D)\n- Only one correct answer per question\n- Questions should be at $difficulty difficulty level\n- Return only valid JSON format\n- Make questions educational and meaningful";

            $request_data = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 2048,
                ]
            ];

            // First test if API key is valid using v1beta
            $debug_logs[] = "Testing API key validity...";
            $api_key_test_url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . GEMINI_API_KEY;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_key_test_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $api_test_result = curl_exec($ch);
            $api_test_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            $debug_logs[] = "API key test: HTTP $api_test_code" . ($curl_error ? " Curl Error: $curl_error" : "");
            
            if ($api_test_code != 200) {
                $debug_logs[] = "API Response: " . substr($api_test_result, 0, 200);
                // Don't fail here, continue with model testing
                $debug_logs[] = "API key test failed, but continuing with model testing...";
            } else {
                $debug_logs[] = "✅ API key is valid";
                
                // Parse available models from the response
                $models_response = json_decode($api_test_result, true);
                if (isset($models_response['models'])) {
                    $debug_logs[] = "Available models count: " . count($models_response['models']);
                    $available_model_names = [];
                    foreach ($models_response['models'] as $model) {
                        if (isset($model['name'])) {
                            $model_name = str_replace('models/', '', $model['name']);
                            $available_model_names[] = $model_name;
                        }
                    }
                    $debug_logs[] = "Available model names: " . implode(', ', array_slice($available_model_names, 0, 10));
                }
            }
            
            // Skip model testing and use the most reliable configuration
            $debug_logs[] = "Using direct model access with Gemini 1.5 configuration...";
            $working_api = ['model' => 'gemini-1.5-flash', 'version' => 'v1beta'];
            $debug_logs[] = "Selected: gemini-1.5-flash with v1beta (Gemini 1.5 model)";
            
            // Optional: Still run model testing for debug info but don't rely on it
            if ($debug_mode) {
                $debug_logs[] = "Running model availability test for debug purposes...";
                $test_result = find_working_gemini_api();
                if ($test_result) {
                    $debug_logs[] = "Model test found: " . $test_result['model'] . " with " . $test_result['version'];
                    $working_api = $test_result; // Use the tested result if available
                }
            }
            
            $selected_model = $working_api['model'];
            $working_api_version = $working_api['version'];
            
            $debug_logs[] = "Using Gemini model: $selected_model";
            $debug_logs[] = "Found working API version: $working_api_version";
            
            // Build the correct API URL
            $api_url = 'https://generativelanguage.googleapis.com/' . $working_api_version . '/models/' . $selected_model . ':generateContent?key=' . GEMINI_API_KEY;
            $debug_logs[] = "API URL: $api_url";
            $debug_logs[] = "Prepared API request data. Sending to Gemini API...";

            // Make API request to Gemini
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            // Debug information
            $debug_info = [
                'http_code' => $http_code,
                'curl_error' => $curl_error,
                'response_length' => strlen($response),
                'api_url' => $api_url,
                'request_data_size' => strlen(json_encode($request_data)),
                'pdf_text_length' => strlen($pdf_text)
            ];
            
            if ($response === false) {
                throw new Exception('CURL Error: ' . $curl_error . ' | Debug: ' . json_encode($debug_info));
            }
            
            if ($http_code !== 200) {
                $error_response = json_decode($response, true);
                $error_message = 'HTTP ' . $http_code . ': ';
                
                if (isset($error_response['error']['message'])) {
                    $error_message .= $error_response['error']['message'];
                } else {
                    $error_message .= 'API request failed';
                }
                
                $error_message .= ' | Response: ' . substr($response, 0, 500);
                $error_message .= ' | Debug: ' . json_encode($debug_info);
                
                throw new Exception($error_message);
            }
            
            $api_response = json_decode($response, true);
            
            // Debug API response structure
            if (!$api_response) {
                throw new Exception('Invalid JSON response from API. Raw response: ' . substr($response, 0, 1000));
            }
            
            // Check for API errors in response
            if (isset($api_response['error'])) {
                throw new Exception('API Error: ' . $api_response['error']['message'] . ' | Code: ' . $api_response['error']['code']);
            }
            
            if (!isset($api_response['candidates'][0]['content']['parts'][0]['text'])) {
                throw new Exception('Invalid response structure from AI service. Response structure: ' . json_encode(array_keys($api_response)) . ' | Full response: ' . substr($response, 0, 1000));
            }
            
            $ai_text = $api_response['candidates'][0]['content']['parts'][0]['text'];
            
            // Debug AI text response
            if (empty($ai_text)) {
                throw new Exception('Empty response from AI service. Full API response: ' . json_encode($api_response));
            }
            
            // Clean up the JSON response (remove markdown formatting if present)
            $ai_text = preg_replace('/```json\s*/', '', $ai_text);
            $ai_text = preg_replace('/```\s*$/', '', $ai_text);
            $ai_text = trim($ai_text);
            
            // Debug cleaned AI text
            $debug_ai_text = substr($ai_text, 0, 500);
            
            $quiz_data = json_decode($ai_text, true);
            
            if (!$quiz_data) {
                throw new Exception('Failed to parse AI JSON response. JSON Error: ' . json_last_error_msg() . ' | AI Text: ' . $debug_ai_text);
            }
            
            if (!isset($quiz_data['questions'])) {
                throw new Exception('AI response missing questions array. Available keys: ' . implode(', ', array_keys($quiz_data)) . ' | AI Text: ' . $debug_ai_text);
            }
            
            if (empty($quiz_data['questions'])) {
                throw new Exception('AI generated empty questions array. Full AI response: ' . $debug_ai_text);
            }
            
            // Create quiz in database using prepared statements
            $created_by = getCurrentUserId();
            $creator_name = sanitizeString(getCurrentUsername() ?? '', 100);
            $status = (getCurrentUserRole() === 'admin') ? 'approved' : 'pending';
            $description = "AI-generated quiz from uploaded PDF document";
            
            $result = $db->executeNonQuery(
                "INSERT INTO quizzes (title, description, created_by, creator_name, status) 
                 VALUES (?, ?, ?, ?, ?)",
                [$quiz_title, $description, $created_by, $creator_name, $status],
                'ssiss'
            );
            
            if (!$result) {
                throw new Exception('Failed to create quiz in database: ' . $db->getLastError());
            }
            
            $quiz_id = $db->getLastInsertId();
            
            // Insert questions using prepared statements
            $questions_inserted = 0;
            foreach ($quiz_data['questions'] as $q) {
                if (!isset($q['question'], $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'], $q['correct_option'])) {
                    continue; // Skip malformed questions
                }
                
                $question = $q['question'];
                $option_a = $q['option_a'];
                $option_b = $q['option_b'];
                $option_c = $q['option_c'];
                $option_d = $q['option_d'];
                $correct = strtoupper(substr($q['correct_option'], 0, 1)); // Ensure single letter
                
                if (!in_array($correct, ['A', 'B', 'C', 'D'])) {
                    $correct = 'A'; // Default to A if invalid
                }
                
                $insert_result = $db->executeNonQuery(
                    "INSERT INTO questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_option) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$quiz_id, $question, $option_a, $option_b, $option_c, $option_d, $correct],
                    'issssss'
                );
                
                if ($insert_result) {
                    $questions_inserted++;
                }
            }
            
            if ($questions_inserted === 0) {
                // If no questions were inserted, delete the quiz
                $db->executeNonQuery("DELETE FROM quizzes WHERE id = ?", [$quiz_id], 'i');
                throw new Exception('Failed to create quiz questions. Please try again.');
            }
            
            // Redirect to success page
            header("Location: ai_quiz_success.php?quiz_id=$quiz_id&questions=$questions_inserted");
            exit;
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            $processing = false;
            logSecurityEvent('ai_quiz_error', "Error: " . $e->getMessage(), 'error');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Quiz Creator - QuizMaster</title>
    <link rel="stylesheet" href="shadcn-style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">QuizMaster</a>
            <div class="nav-links">
                <span class="nav-link">Hello, <?php echo htmlspecialchars(getCurrentUsername()); ?>!</span>
                <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container"><?php if ($processing): ?>
            <!-- Processing Screen -->
            <div class="card text-center">
                <div class="card-content p-6">
                    <div class="processing-animation">
                        <div class="ai-brain">🤖</div>
                        <div class="processing-dots">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold mb-4">🧠 AI is Creating Your Quiz...</h2>
                    <p class="text-muted-foreground mb-6">Please wait while Gemini AI analyzes your PDF and generates quiz questions.</p>
                    <div class="space-y-2">
                        <div class="flex items-center text-sm">
                            <span class="mr-2">📄</span> Reading PDF content
                        </div>
                        <div class="flex items-center text-sm">
                            <span class="mr-2">🤖</span> AI analyzing content
                        </div>
                        <div class="flex items-center text-sm">
                            <span class="mr-2">❓</span> Generating questions
                        </div>
                        <div class="flex items-center text-sm text-muted-foreground">
                            <span class="mr-2">✅</span> Finalizing quiz
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Main Form -->
            <div class="ai-creator-header text-center mb-6">
                <h2 class="text-2xl font-bold mb-2">🤖 Gemini AI Quiz Creator</h2>
                <p class="text-muted-foreground">Upload a PDF document and let AI create engaging quiz questions automatically</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-destructive mb-6">
                    <p>❌ <?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($debug_mode && !empty($debug_logs)): ?>
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">🔧 Debug Information</h3>
                    </div>
                    <div class="card-content">
                        <div class="space-y-1 text-sm font-mono">
                            <?php foreach ($debug_logs as $log): ?>
                                <div class="text-muted-foreground">✓ <?php echo htmlspecialchars($log); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="space-y-6">
                <?= csrfField() ?>
                <!-- Quiz Details -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📝 Quiz Information</h3>
                    </div>
                    <div class="card-content">
                        <div class="form-group">
                            <label class="label">Quiz Title:</label>
                            <input type="text" name="quiz_title" class="input" required 
                                   placeholder="e.g., Biology Chapter 5 Quiz" 
                                   value="<?php echo htmlspecialchars($_POST['quiz_title'] ?? ''); ?>"
                                   maxlength="255" minlength="3">
                        </div>
                    </div>
                </div>

                <!-- PDF Upload -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📄 Upload PDF Document</h3>
                    </div>
                    <div class="card-content">
                        <div class="upload-area">
                            <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" required class="hidden">
                            <label for="pdf_file" class="cursor-pointer">
                                <div class="text-4xl mb-4">📄</div>
                                <div class="upload-text">
                                    <strong>Click to upload PDF</strong>
                                    <span class="block text-sm text-muted-foreground">or drag and drop</span>
                                </div>
                                <div class="text-sm text-muted-foreground mt-2">
                                    PDF files only • Max 10MB
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- AI Settings -->
                <div class="form-section">
                    <h3>⚙️ AI Generation Settings</h3>
                    
                    <div class="settings-grid">
                        <div class="form-group">
                            <label>Number of Questions:</label>
                            <select name="num_questions" required>
                                <option value="5" <?php echo (isset($_POST['num_questions']) && $_POST['num_questions'] == '5') ? 'selected' : ''; ?>>5 Questions</option>
                                <option value="10" <?php echo (isset($_POST['num_questions']) && $_POST['num_questions'] == '10') ? 'selected' : 'selected'; ?>>10 Questions</option>
                                <option value="15" <?php echo (isset($_POST['num_questions']) && $_POST['num_questions'] == '15') ? 'selected' : ''; ?>>15 Questions</option>
                                <option value="20" <?php echo (isset($_POST['num_questions']) && $_POST['num_questions'] == '20') ? 'selected' : ''; ?>>20 Questions</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Difficulty Level:</label>
                            <select name="difficulty" required>
                                <option value="easy" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] == 'easy') ? 'selected' : ''; ?>>Easy</option>
                                <option value="medium" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] == 'medium') ? 'selected' : 'selected'; ?>>Medium</option>
                                <option value="hard" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] == 'hard') ? 'selected' : ''; ?>>Hard</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- AI Features Info -->
                <div class="ai-features-info">
                    <h3>✨ What AI will do:</h3>
                    <div class="features-grid">
                        <div class="feature-item">
                            <span class="feature-icon">📖</span>
                            <span>Extract and analyze PDF content</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">🧠</span>
                            <span>Generate relevant questions</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">✅</span>
                            <span>Create multiple choice answers</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">🎯</span>
                            <span>Match specified difficulty level</span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="submit-section">
                    <button type="submit" class="btn-ai-generate">
                        🤖 Generate Quiz with AI
                    </button>
                    <p class="generation-note">
                        ⏱️ Generation typically takes 10-30 seconds depending on document size
                    </p>
                </div>

                <!-- Test Mode (for debugging) -->
                <div class="test-section">
                    <h4>🧪 Test Mode (No PDF Required)</h4>
                    <p>Test the AI quiz generation with sample content</p>
                    <button type="submit" name="test_mode" class="btn-test">
                        🧪 Test AI Generation
                    </button>
                </div>
            </form>
            
            <div class="back-actions">
                <a href="create_quiz.php" class="btn-secondary">⬅️ Back to Quiz Options</a>
                <a href="index.php" class="btn-tertiary">🏠 Home</a>
                <?php if (!$debug_mode): ?>
                    <a href="ai_quiz_creator.php?debug=1" class="btn-debug">🔧 Enable Debug Mode</a>
                <?php else: ?>
                    <a href="ai_quiz_creator.php" class="btn-debug">🔧 Disable Debug Mode</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
  </main>

  <script src="dark-mode.js"></script>
  <script>
        // File upload preview
        document.getElementById('pdf_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const label = document.querySelector('.upload-label .upload-text strong');
            
            if (file) {
                label.textContent = file.name;
                document.querySelector('.upload-area').classList.add('file-selected');
            }
        });

        // Processing animation
        <?php if ($processing): ?>
        let stepIndex = 0;
        const steps = document.querySelectorAll('.processing-steps .step');
        
        function activateNextStep() {
            if (stepIndex < steps.length) {
                steps[stepIndex].classList.add('active');
                stepIndex++;
                setTimeout(activateNextStep, 2000);
            }
        }
        
        setTimeout(activateNextStep, 1000);
        <?php endif; ?>
    </script>
</body>
</html>