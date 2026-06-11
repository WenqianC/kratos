;
// 请在第三行开始编写代码

document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-dn-page-jump]');

    if (!button) {
        return;
    }

    var select = button.parentNode.querySelector('[data-dn-page-select]');

    if (select && select.value) {
        window.location.assign(select.value);
    }
});
