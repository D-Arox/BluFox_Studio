<?php

$navItems = [
    ['url' => '/', 'title' => 'Home', 'icon' => 'home'],
    ['url' => '/portfolio', 'title' => 'Portfolio', 'icon' => 'briefcase'],
    ['url' => '/products', 'title' => 'Products', 'icon' => 'package'],
    ['url' => '/vault', 'title' => 'Vault', 'icon' => 'database'],
    ['url' => '/documentation', 'title' => 'Documentation', 'icon' => 'book'],
    ['url' => '/contact', 'title' => 'Contact', 'icon' => 'mail']
];

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<nav class="navigation" role="navigation">
    <ul class="nav-menu">
        <?php foreach ($navItems as $item): ?>
            <li class="nav-item">
                <a href="<?php echo $item['url']; ?>" 
                   class="nav-link <?php echo $currentPath === $item['url'] ? 'active' : ''; ?>"
                   <?php echo $currentPath === $item['url'] ? 'aria-current="page"' : ''; ?>>
                    <i class="icon-<?php echo $item['icon']; ?>"></i>
                    <span><?php echo $item['title']; ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>