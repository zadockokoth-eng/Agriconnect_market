
<?php
require_once 'functions.php';

$pageTitle = 'About AgriMarket';

require_once 'header.php';
?>

<main>

    <!-- ABOUT SECTION -->
    <section class="about-hero">
        <div class="container">
            <h1>About AgriMarket</h1>

            <p>
                AgriMarket is an online agricultural marketplace
                that connects farmers with buyers, making it easier
                to advertise, discover and purchase farm produce.
            </p>

            <a href="<?= e(BASE_URL) ?>/catalogue.php" class="btn">
                Explore Produce
            </a>
        </div>
    </section>

    <!-- SYSTEM OVERVIEW -->
    <section class="about-section">
        <div class="container">

            <div class="section-heading">
                <h2>What Is AgriMarket?</h2>

                <p>
                    AgriMarket provides a digital platform where
                    farmers can list agricultural products and buyers
                    can find available produce and place orders online.
                </p>
            </div>

            <div class="about-grid">

                <div class="about-card">
                    <i class="fas fa-tractor"></i>
                    <h3>Farmer Services</h3>

                    <p>
                        Farmers can register, add agricultural products,
                        update their listings and manage their produce.
                    </p>
                </div>

                <div class="about-card">
                    <i class="fas fa-shopping-basket"></i>
                    <h3>Buyer Services</h3>

                    <p>
                        Buyers can browse approved produce, add products
                        to their shopping cart and place orders.
                    </p>
                </div>

                <div class="about-card">
                    <i class="fas fa-user-shield"></i>
                    <h3>Administrator Services</h3>

                    <p>
                        Administrators can manage user accounts,
                        categories, product approvals and orders.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- MISSION AND OBJECTIVES -->
    <section class="mission-section">
        <div class="container">

            <div class="mission-card">
                <h2>Our Mission</h2>

                <p>
                    To provide a convenient online marketplace that
                    helps farmers reach buyers and makes agricultural
                    produce easier to access.
                </p>
            </div>

            <div class="mission-card">
                <h2>Our Objectives</h2>

                <ul>
                    <li>Connect farmers with potential buyers.</li>
                    <li>Allow farmers to advertise agricultural produce.</li>
                    <li>Enable buyers to browse and order products online.</li>
                    <li>Provide shopping cart and order management facilities.</li>
                    <li>Allow administrators to manage marketplace activities.</li>
                </ul>
            </div>

        </div>
    </section>

    <!-- HOW THE SYSTEM WORKS -->
    <section class="about-section">
        <div class="container">

            <div class="section-heading">
                <h2>How AgriMarket Works</h2>

                <p>
                    The system provides separate services for
                    farmers, buyers and administrators.
                </p>
            </div>

            <div class="steps">

                <div class="step">
                    <span>1</span>
                    <h3>Create an Account</h3>
                    <p>
                        Farmers and buyers register and log in
                        using their accounts.
                    </p>
                </div>

                <div class="step">
                    <span>2</span>
                    <h3>List or Browse Produce</h3>
                    <p>
                        Farmers add products while buyers browse
                        available approved produce.
                    </p>
                </div>

                <div class="step">
                    <span>3</span>
                    <h3>Place Orders</h3>
                    <p>
                        Buyers add products to their carts
                        and submit their orders.
                    </p>
                </div>

                <div class="step">
                    <span>4</span>
                    <h3>Manage Activities</h3>
                    <p>
                        Users manage their activities while
                        administrators oversee the marketplace.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- CALL TO ACTION -->
    <section class="about-cta">
        <div class="container">

            <h2>Start Using AgriMarket Today</h2>

            <p>
                Register as a farmer or buyer and become part
                of the AgriMarket marketplace.
            </p>

            <div class="cta-links">
                <a href="<?= e(BASE_URL) ?>/form.php?action=register" class="btn">
                    Create Account
                </a>

                <a href="<?= e(BASE_URL) ?>/catalogue.php" class="btn btn-light">
                    View Produce
                </a>
            </div>

        </div>
    </section>

</main>

<style>
.container {
    width: 90%;
    max-width: 1150px;
    margin: auto;
}

.about-hero {
    background: #065f46;
    color: white;
    text-align: center;
    padding: 65px 20px;
}

.about-hero h1 {
    font-size: 38px;
    margin-bottom: 15px;
}

.about-hero p {
    max-width: 750px;
    margin: 0 auto 25px;
    line-height: 1.8;
}

.about-section {
    padding: 55px 0;
}

.section-heading {
    text-align: center;
    max-width: 750px;
    margin: 0 auto 35px;
}

.section-heading h2,
.mission-card h2 {
    color: #065f46;
    margin-bottom: 15px;
}

.section-heading p,
.about-card p,
.step p {
    color: #64748b;
    line-height: 1.8;
}

.about-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
}

.about-card,
.mission-card,
.step {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.07);
}

.about-card i {
    font-size: 32px;
    color: #059669;
    margin-bottom: 15px;
}

.about-card h3,
.step h3 {
    color: #065f46;
    margin-bottom: 12px;
}

.mission-section {
    background: #ecfdf5;
    padding: 50px 0;
}

.mission-card {
    margin-bottom: 20px;
}

.mission-card p,
.mission-card li {
    color: #475569;
    line-height: 1.9;
}

.mission-card ul {
    padding-left: 22px;
}

.steps {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
}

.step span {
    display: inline-flex;
    width: 38px;
    height: 38px;
    background: #059669;
    color: white;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: bold;
    margin-bottom: 15px;
}

.about-cta {
    background: #064e3b;
    color: white;
    text-align: center;
    padding: 55px 20px;
}

.about-cta h2 {
    font-size: 28px;
    margin-bottom: 15px;
}

.about-cta p {
    margin-bottom: 25px;
}

.cta-links {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 15px;
}

.btn {
    display: inline-block;
    padding: 12px 23px;
    background: #fbbf24;
    color: #064e3b;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
}

.btn-light {
    background: white;
    color: #065f46;
}

.btn:hover {
    opacity: 0.85;
}

@media(max-width: 800px) {
    .about-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .steps {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width: 550px) {
    .about-grid,
    .steps {
        grid-template-columns: 1fr;
    }

    .about-hero h1 {
        font-size: 30px;
    }
}
</style>

<?php require_once 'footer.php'; ?>
