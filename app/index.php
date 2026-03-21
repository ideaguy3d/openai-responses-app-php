<?php
// public/index.php
session_start();

// Initialize session data if not set
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [];
}
if (!isset($_SESSION['tools'])) {
    $_SESSION['tools'] = [
        'webSearchEnabled' => false,
        'fileSearchEnabled' => false,
        'functionsEnabled' => true,
        'codeInterpreterEnabled' => false,
        'mcpEnabled' => false,
        'vectorStore' => ['id' => '', 'name' => ''],
        'webSearchConfig' => ['user_location' => ['type' => 'approximate', 'country' => '', 'city' => '', 'region' => '']],
        'mcpConfig' => ['server_label' => '', 'server_url' => '', 'allowed_tools' => '', 'skip_approval' => true],
    ];
}
?>


<?php include dirname(__DIR__) . '/templates/header.php'; ?>

<div class="flex justify-center h-screen">
    <!-- Chat area (70%) -->
    <div class="w-full md:w-[70%]">
        <?php include dirname(__DIR__) . '/templates/chat.php'; ?>
    </div>
    <!-- Tools panel (30%) -->
    <div class="hidden md:block w-[30%]">
        <?php include dirname(__DIR__) . '/templates/tools-panel.php'; ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/templates/footer.php'; ?>
