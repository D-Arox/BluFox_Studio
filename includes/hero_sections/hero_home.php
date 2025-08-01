<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="container hero-container hero-container-home">
    <h1 class="hero-title">
        BluFox Studio
    </h1>
    <p class="hero-subtitle">
        Transform your Roblox game ideas into reality with our expert development team and revolutionary Vantara
        Framework. From concept to launch, we deliver exceptional gaming experiences.
    </p>
    <div class="hero-stats">
        <div class="hero-stat card">
            <span class="hero-stat-number">0+</span>
            <span class="hero-stat-label">Games Developed</span>
        </div>
        <div class="hero-stat card">
            <span class="hero-stat-number">0+</span>
            <span class="hero-stat-label">Players Reached</span>
        </div>
        <!-- <div class="hero-stat card">
            <span class="hero-stat-number">0%</span>
            <span class="hero-stat-label">Client Satisfaction</span>
        </div> -->
    </div>
    <div class="scroll-indicator" onclick="scrollToNextSection()">
        <div class="scroll-mouse">
            <div class="scroll-wheel"></div>
        </div>
        <span class="scroll-text">Scroll</span>
    </div>
</div>
