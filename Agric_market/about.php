```php
<?php
require_once 'functions.php';

$pageTitle = 'About Us';

require_once 'header.php';
?>

<main class="about-page">

    <!-- ABOUT HERO SECTION -->
    <section class="hero">
        <div class="hero-content">
            <span class="hero-subtitle">Welcome to AgriMarket</span>

            <h1>About AgriMarket</h1>

            <p>
                We are committed to transforming agricultural trade
                by connecting farmers directly with buyers through
                a convenient and reliable digital marketplace.
            </p>

            <a href="<?= e(BASE_URL) ?>/catalogue.php" class="btn">
                Explore Produce
            </a>
        </div>
    </section>

    <!-- MISSION, VISION AND OBJECTIVES -->
    <section class="section about-information">

        <div class="section-heading">
            <span>Who We Are</span>
            <h2>Growing Agriculture Through Technology</h2>

            <p>
                AgriMarket makes it easier for farmers to reach customers
                and for buyers to access fresh agricultural produce.
            </p>
        </div>

        <div class="about-grid">

            <div class="card about-card">
                <div class="about-icon">
                    <i class="fas fa-bullseye"></i>
                </div>

                <h2>Our Mission</h2>

                <p>
                    To empower smallholder farmers by providing a digital
                    marketplace where they can advertise agricultural
                    produce, reach reliable buyers and improve their
                    income through better market access.
                </p>
            </div>

            <div class="card about-card">
                <div class="about-icon">
                    <i class="fas fa-eye"></i>
                </div>

                <h2>Our Vision</h2>

                <p>
                    To become a trusted digital agricultural marketplace
                    that improves access to markets, promotes sustainable
                    farming and supports food security in Kenya.
                </p>
            </div>

            <div class="card about-card">
                <div class="about-icon">
                    <i class="fas fa-list-check"></i>
                </div>

                <h2>Our Objectives</h2>

                <ul class="objectives-list">
                    <li>Connect farmers with potential buyers.</li>
                    <li>Improve access to agricultural markets.</li>
                    <li>Promote transparent produce pricing.</li>
                    <li>Support efficient online ordering.</li>
                    <li>Encourage digital agriculture in Kenya.</li>
                </ul>
            </div>

        </div>

    </section>

    <!-- WHY AGRIMARKET -->
    <section class="section why-us">

        <div class="section-heading">
            <span>Why Choose Us?</span>

            <h2>Growing Together Through Agriculture</h2>

            <p>
                A convenient marketplace built to connect
                farmers and buyers.
            </p>
        </div>

        <div class="features">

            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-tractor"></i>
                </div>

                <h3>Direct Farmer Connections</h3>

                <p>
                    Farmers can showcase their produce and connect
                    with customers through one marketplace.
                </p>
            </div>

            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-basket-shopping"></i>
                </div>

                <h3>Convenient Shopping</h3>

                <p>
                    Buyers can browse agricultural products,
                    manage shopping carts and place orders online.
                </p>
            </div>

            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-shield-halved"></i>
                </div>

                <h3>Managed Listings</h3>

                <p>
                    Administrators can review product listings
                    and manage marketplace accounts.
                </p>
            </div>

        </div>

    </section>

    <!-- CALL TO ACTION -->
    <section class="section about-cta">

        <h2>Be Part of the AgriMarket Community</h2>

        <p>
            Whether you are a farmer looking for customers or a buyer
            searching for fresh produce, AgriMarket is here to help.
        </p>

        <div class="cta-buttons">
            <a href="<?= e(BASE_URL) ?>/form.php?action=register" class="btn">
                Join AgriMarket
            </a>

            <a href="<?= e(BASE_URL) ?>/catalogue.php" class="btn btn-outline">
                Browse Products
            </a>
        </div>

    </section>

</main>

<style>
.about-page {
    width: 100%;
}

.hero {
    background: linear-gradient(135deg, #065f46, #047857);
    color: #fff;
    text-align: center;
    padding: 85px 20px;
}

.hero-content {
    max-width: 850px;
    margin: auto;
}

.hero-subtitle {
    color: #fcd34d;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.hero h1 {
    font-size: clamp(2.2rem, 5vw, 3.5rem);
    margin: 15px 0;
}

.hero p {
    max-width: 700px;
    margin: 0 auto 25px;
    line-height: 1.8;
    font-size: 1.1rem;
}

.section {
    padding: 65px 6%;
}

.section-heading {
    text-align: center;
    max-width: 750px;
    margin: 0 auto 40px;
}

.section-heading span {
    color: #059669;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
}

.section-heading h2 {
    color: #064e3b;
    font-size: clamp(1.7rem, 4vw, 2.4rem);
    margin: 12px 0;
}

.section-heading p {
    color: #64748b;
    line-height: 1.7;
}

.about-grid,
.features {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 25px;
    max-width: 1200px;
    margin: auto;
}

.card,
.feature {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.07);
}

.about-card h2,
.feature h3 {
    color: #065f46;
    margin: 15px 0;
}

.about-card p,
.feature p {
    color: #64748b;
    line-height: 1.8;
}

.about-icon,
.feature-icon {
    width: 58px;
    height: 58px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #d1fae5;
    color: #047857;
    border-radius: 50%;
    font-size: 25px;
}

.objectives-list {
    padding-left: 20px;
    line-height: 2;
    color: #64748b;
}

.why-us {
    background: #f0fdf4;
}

.feature {
    text-align: center;
}

.feature-icon {
    margin: 0 auto;
}

.about-cta {
    background: #064e3b;
    color: #fff;
    text-align: center;
}

.about-cta h2 {
    font-size: clamp(1.7rem, 4vw, 2.3rem);
    margin-bottom: 15px;
}

.about-cta p {
    max-width: 700px;
    margin: 0 auto 25px;
    line-height: 1.8;
    color: #d1fae5;
}

.cta-buttons {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 15px;
}

.btn {
    display: inline-block;
    background: #fbbf24;
    color: #064e3b;
    padding: 13px 25px;
    border-radius: 7px;
    text-decoration: none;
    font-weight: 700;
    transition: 0.3s ease;
}

.btn:hover {
    background: #f59e0b;
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    color: #fff;
    border: 2px solid #fff;
}

.btn-outline:hover {
    background: #fff;
    color: #064e3b;
}

@media (max-width: 850px) {
    .about-grid,
    .features {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .section {
        padding: 45px 18px;
    }

    .about-grid,
    .features {
        grid-template-columns: 1fr;
    }

    .hero {
        padding: 60px 18px;
    }
}
</style>

<?php require_once 'footer.php'; ?>
```