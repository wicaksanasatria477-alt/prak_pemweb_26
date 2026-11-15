// js/script.js - JavaScript sederhana untuk semua halaman

// 1. Hitung jumlah karakter pada textarea review
var kotakReview = document.getElementById("isi_review");
var penghitung = document.getElementById("hitung");
if (kotakReview && penghitung) {
    kotakReview.addEventListener("input", function () {
        penghitung.textContent = kotakReview.value.length;
    });
}

// 2. Konfirmasi sebelum menghapus (form dengan class "hapus")
document.querySelectorAll("form.hapus").forEach(function (form) {
    form.addEventListener("submit", function (event) {
        if (!confirm("Are you sure you want to delete this data?")) {
            event.preventDefault();
        }
    });
});

// 3. Modal tambah / edit di halaman admin:
//    tombol membawa data-nama_kolom="nilai", lalu diisikan ke input yang namanya sama
var modalForm = document.getElementById("formModal");
if (modalForm) {
    modalForm.addEventListener("show.bs.modal", function (event) {
        var tombol = event.relatedTarget;
        var form = modalForm.querySelector("form");
        form.reset();
        modalForm.querySelector(".modal-title").textContent = tombol.dataset.judulModal;
        for (var nama in tombol.dataset) {
            if (form.elements[nama] && form.elements[nama].type !== "file") {
                form.elements[nama].value = tombol.dataset[nama];
            }
        }
    });
}
