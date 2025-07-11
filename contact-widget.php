<style>
/* === Contact Widget Styles === */
.contact-widget {
    position: fixed;
    bottom: 68px;
    right: 20px;
    z-index: 9999;
    font-family: Arial, sans-serif;
}

.contact-button {
    background-color:rgb(255, 255, 255);
    color: #fff;
    border: none;
    border-radius: 60%;
    width: 60px;
    height: 60px;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(109, 129, 214, 0.3);
    transition: background 0.5s;
}

.contact-button img {
    width: 95%;
    height: 100%;
    object-fit: contain;
}

.contact-button:hover {
    background-color: #333;
}

.contact-popup {
    display: none;
    flex-direction: column;
    gap: 10px;
    position: absolute;
    bottom: 70px;
    right: 0;
    background-color: #1c1c1c;
    color: #fff;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 6px 16px rgba(0,0,0,0.4);
    width: 250px;
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.contact-popup.show {
    display: flex;
    opacity: 1;
    transform: translateY(0);
}

.contact-link {
    display: flex;
    align-items: center;
    gap: 10px;
    color:rgb(119, 207, 255);
    text-decoration: none;
    font-weight: bold;
    transition: color 0.2s;
}

.contact-link:hover {
    color: #fff;
}

.contact-link svg {
    width: 20px;
    height: 20px;
    fill: currentColor;
}
</style>

<div class="contact-widget">
    <button class="contact-button" onclick="toggleContactPopup()">
        <img src="/zerous-shop/assets//cs.webp" alt="Contact" />
    </button>
    <div class="contact-popup" id="contactPopup">
        <a class="contact-link" href="https://wa.me/6282323796415" target="_blank">
            <svg viewBox="0 0 24 24"><path d="M20.5 3.5A11.85 11.85 0 0012 0C5.37 0 0 5.37 0 12a11.82 11.82 0 001.67 6L0 24l6.33-1.66A11.85 11.85 0 0012 24c6.63 0 12-5.37 12-12 0-3.18-1.23-6.15-3.5-8.5zM12 22a9.91 9.91 0 01-5.24-1.46L4 20l1.45-2.76A9.93 9.93 0 012 12c0-5.52 4.48-10 10-10s10 4.48 10 10-4.48 10-10 10zm5.3-7.7c-.27-.14-1.58-.78-1.83-.87s-.42-.14-.6.14-.69.87-.85 1.05-.31.2-.57.07a8.18 8.18 0 01-2.4-1.49 9.09 9.09 0 01-1.67-2.07c-.17-.28 0-.44.13-.57.13-.13.28-.31.42-.46.14-.15.19-.26.28-.43.1-.17.05-.32 0-.46s-.6-1.45-.83-1.99c-.22-.52-.44-.45-.6-.46H8c-.17 0-.45.07-.68.33A5.7 5.7 0 006.1 9.2c-.4.68-.55 1.48-.14 2.29.4.81 1.55 3.05 3.73 4.28 2.18 1.22 2.61.81 3.09.76.47-.05 1.58-.64 1.81-1.25.22-.61.22-1.13.16-1.24s-.23-.18-.5-.32z"/></svg>
            WhatsApp
        </a>
        <a class="contact-link" href="https://discord.gg/855r9ZAGXp" target="_blank">
            <svg viewBox="0 0 24 24"><path d="M20.317 4.369a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037 13.805 13.805 0 00-.651 1.34 18.589 18.589 0 00-5.524 0 13.24 13.24 0 00-.658-1.34.077.077 0 00-.078-.037A19.736 19.736 0 003.683 4.37a.07.07 0 00-.032.027C.533 9.28-.32 14.082.099 18.832a.078.078 0 00.028.053 19.911 19.911 0 005.993 3.04.078.078 0 00.084-.027c.462-.63.873-1.295 1.226-1.993a.076.076 0 00-.041-.105 13.206 13.206 0 01-1.885-.89.078.078 0 01-.007-.132c.127-.095.255-.194.378-.291a.074.074 0 01.077-.01c3.926 1.791 8.18 1.791 12.061 0a.073.073 0 01.078.01c.123.096.251.196.379.291a.078.078 0 01-.005.132 12.511 12.511 0 01-1.886.89.075.075 0 00-.04.106c.36.698.77 1.362 1.227 1.993a.077.077 0 00.084.028 19.888 19.888 0 005.992-3.04.077.077 0 00.028-.053c.5-5.177-.838-9.947-3.571-14.436a.06.06 0 00-.03-.027zM8.02 15.331c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.954-2.418 2.157-2.418 1.21 0 2.175 1.093 2.157 2.419 0 1.333-.954 2.418-2.157 2.418zm7.96 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.954-2.418 2.157-2.418 1.21 0 2.175 1.093 2.157 2.419 0 1.333-.947 2.418-2.157 2.418z"/></svg>
            Discord
        </a>
        <a class="contact-link" href="https://t.me/zeroushop" target="_blank">
            <svg viewBox="0 0 24 24"><path d="M9.993 15.938l-.393 5.57c.564 0 .81-.242 1.1-.531l2.635-2.49 5.463 3.99c1.002.552 1.718.264 1.973-.927l3.578-16.742c.31-1.447-.534-2.012-1.511-1.658L1.465 9.314c-1.41.539-1.39 1.3-.24 1.644l4.894 1.526L19.5 6.262c.73-.449 1.397-.2.849.287z"/></svg>
            Telegram
        </a>
        <a class="contact-link" href="https://instagram.com/yourhandle" target="_blank">
            <svg viewBox="0 0 24 24"><path d="M7.75 2A5.75 5.75 0 002 7.75v8.5A5.75 5.75 0 007.75 22h8.5A5.75 5.75 0 0022 16.25v-8.5A5.75 5.75 0 0016.25 2h-8.5zM12 7.25a4.75 4.75 0 110 9.5 4.75 4.75 0 010-9.5zm0 1.5a3.25 3.25 0 100 6.5 3.25 3.25 0 000-6.5zm5.25-.75a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5z"/></svg>
            Instagram
        </a>
    </div>
</div>

<script>
function toggleContactPopup() {
    const popup = document.getElementById("contactPopup");
    popup.classList.toggle("show");
}
</script>
