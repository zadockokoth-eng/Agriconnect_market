        </main>

        <footer style="
            padding:20px 30px;
            text-align:center;
            color:#64748b;
            font-size:13px;
            background:white;
            border-top:1px solid #e5e7eb;
        ">
            &copy; <?= date('Y') ?> AgriMarket.
            Fresh From Farm to Table. All rights reserved.
        </footer>

</div>

<script>
const menuToggle = document.getElementById('menuToggle');
const buyerSidebar = document.getElementById('buyerSidebar');

if (menuToggle && buyerSidebar) {
    menuToggle.addEventListener('click', function () {
        buyerSidebar.classList.toggle('open');

        const isOpen = buyerSidebar.classList.contains('open');
        menuToggle.setAttribute('aria-expanded', String(isOpen));
    });
}
</script>

</body>
</html>