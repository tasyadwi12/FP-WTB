<?php
/**
 * Navbar Component
 * File: includes/navbar.php
 * Helper functions untuk breadcrumb dan page header
 */

/**
 * Render Breadcrumb
 */
function renderBreadcrumb($items = []) {
    if (empty($items)) return;
    
    echo '<nav aria-label="breadcrumb" class="mb-3">';
    echo '<ol class="breadcrumb">';
    
    foreach ($items as $index => $item) {
        $isLast = ($index === count($items) - 1);
        
        if ($isLast) {
            echo '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($item['label']) . '</li>';
        } else {
            $url = isset($item['url']) ? htmlspecialchars($item['url']) : '#';
            echo '<li class="breadcrumb-item"><a href="' . $url . '">' . htmlspecialchars($item['label']) . '</a></li>';
        }
    }
    
    echo '</ol>';
    echo '</nav>';
}

/**
 * Render Page Header
 */
function renderPageHeader($title, $subtitle = '', $buttons = []) {
    echo '<div class="page-header mb-4">';
    echo '<div class="row align-items-center">';
    
    // Title Section
    echo '<div class="col">';
    echo '<h2 class="page-title mb-1">' . htmlspecialchars($title) . '</h2>';
    if ($subtitle) {
        echo '<p class="text-muted mb-0">' . htmlspecialchars($subtitle) . '</p>';
    }
    echo '</div>';
    
    // Buttons Section
    if (!empty($buttons)) {
        echo '<div class="col-auto">';
        foreach ($buttons as $button) {
            $class = $button['class'] ?? 'btn-primary';
            $icon = $button['icon'] ?? '';
            $label = $button['label'] ?? 'Button';
            $url = $button['url'] ?? '#';
            $attr = $button['attr'] ?? '';
            
            echo '<a href="' . htmlspecialchars($url) . '" class="btn ' . $class . '" ' . $attr . '>';
            if ($icon) echo '<i class="' . $icon . ' me-2"></i>';
            echo htmlspecialchars($label);
            echo '</a>';
        }
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}

/**
 * Render Search Bar
 */
function renderSearchBar($placeholder = 'Cari...', $action = '', $name = 'search') {
    echo '<div class="search-bar mb-4">';
    echo '<form method="GET" action="' . htmlspecialchars($action) . '" class="position-relative">';
    echo '<input type="text" class="form-control form-control-lg ps-5" ';
    echo 'name="' . htmlspecialchars($name) . '" ';
    echo 'placeholder="' . htmlspecialchars($placeholder) . '" ';
    echo 'value="' . htmlspecialchars($_GET[$name] ?? '') . '">';
    echo '<i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>';
    echo '</form>';
    echo '</div>';
}
?>

<style>
/* Breadcrumb Styles */
.breadcrumb {
    background: transparent;
    padding: 0;
    margin: 0;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #6c757d;
}

.breadcrumb-item a {
    color: #6366f1;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    text-decoration: underline;
}

/* Page Header Styles */
.page-header {
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
}

/* Search Bar Styles */
.search-bar {
    max-width: 600px;
}

.search-bar .form-control:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}
</style>