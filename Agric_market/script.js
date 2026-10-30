document.addEventListener("DOMContentLoaded", function () {

    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");

    if (menuToggle && sidebar) {
        menuToggle.addEventListener("click", function () {
            sidebar.classList.toggle("active");
        });
    }

    // Close mobile sidebar after clicking a link
    document.querySelectorAll(".sidebar-nav a").forEach(link => {
        link.addEventListener("click", function () {
            if (window.innerWidth <= 768) {
                sidebar?.classList.remove("active");
            }
        });
    });

    // Product search
    const searchInput = document.getElementById("productSearch");

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            const search = this.value.toLowerCase();

            document.querySelectorAll(".product-card").forEach(card => {
                const name = card.textContent.toLowerCase();

                card.style.display =
                    name.includes(search) ? "" : "none";
            });
        });
    }

    // Confirmation before deleting records
    document.querySelectorAll(".delete-form").forEach(form => {
        form.addEventListener("submit", function (event) {
            if (!confirm("Are you sure you want to delete this record?")) {
                event.preventDefault();
            }
        });
    });

});