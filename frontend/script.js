// script.js

const API_URL = 'http://your-backend-domain.com/api/crawler.php'; // Asenda oma backend URL-iga
const API_KEY = 'YOUR_SECURE_API_KEY'; // Asenda oma API võtmega

document.getElementById('crawlButton').addEventListener('click', () => {
    startCrawling();
});

document.getElementById('searchInput').addEventListener('input', function() {
    filterCategories(this.value);
});

function startCrawling() {
    document.getElementById('status').innerText = 'Kaapimine käib...';
    fetch(API_URL, {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + API_KEY
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Viga API päringus');
        }
        return response.json();
    })
    .then(data => {
        document.getElementById('status').innerText = 'Kaapimine valmis!';
        displayData(data);
    })
    .catch(error => {
        document.getElementById('status').innerText = 'Viga: ' + error.message;
        console.error('Error:', error);
    });
}

let categoryChart, priceChart, popularityChart;

function displayData(data) {
    // Kuva kategooriad tabelis
    populateCategoriesTable(data.categories);

    // Joonista kategooriate graafik
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    if (categoryChart) categoryChart.destroy();
    categoryChart = new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: Object.keys(data.categories),
            datasets: [{
                data: Object.values(data.categories),
                backgroundColor: generateColors(Object.keys(data.categories).length)
            }]
        },
        options: {
            responsive: true
        }
    });

    // Hinnaklassi jaotus (näiteks jagatud vahemikesse)
    const priceRanges = {
        '0-50': 0,
        '51-100': 0,
        '101-200': 0,
        '201+': 0
    };
    data.products.forEach(product => {
        const price = parseFloat(product.price.replace(/[^0-9.]/g, ''));
        if (price <= 50) priceRanges['0-50']++;
        else if (price <= 100) priceRanges['51-100']++;
        else if (price <= 200) priceRanges['101-200']++;
        else priceRanges['201+']++;
    });

    const priceCtx = document.getElementById('priceChart').getContext('2d');
    if (priceChart) priceChart.destroy();
    priceChart = new Chart(priceCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(priceRanges),
            datasets: [{
                label: 'Toodete Arv',
                data: Object.values(priceRanges),
                backgroundColor: 'rgba(75, 192, 192, 0.6)'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Populaarsuse trend (näiteks toodete arv kategooriate kaupa)
    const popularityCtx = document.getElementById('popularityChart').getContext('2d');
    if (popularityChart) popularityChart.destroy();
    popularityChart = new Chart(popularityCtx, {
        type: 'line',
        data: {
            labels: Object.keys(data.categories),
            datasets: [{
                label: 'Toodete Arv',
                data: Object.values(data.categories),
                fill: false,
                borderColor: 'rgba(153, 102, 255, 1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true
        }
    });
}

function populateCategoriesTable(categories) {
    const tableBody = document.querySelector('#categoriesTable tbody');
    tableBody.innerHTML = ''; // Tühjenda tabel

    for (const [category, count] of Object.entries(categories)) {
        const row = tableBody.insertRow();
        const cell1 = row.insertCell(0);
        const cell2 = row.insertCell(1);
        cell1.textContent = category;
        cell2.textContent = count;
    }
}

function filterCategories(query) {
    const tableRows = document.querySelectorAll('#categoriesTable tbody tr');
    tableRows.forEach(row => {
        const category = row.cells[0].textContent.toLowerCase();
        if (category.includes(query.toLowerCase())) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function generateColors(num) {
    const colors = [];
    for (let i = 0; i < num; i++) {
        const r = Math.floor(Math.random() * 255);
        const g = Math.floor(Math.random() * 255);
        const b = Math.floor(Math.random() * 255);
        colors.push(`rgba(${r}, ${g}, ${b}, 0.6)`);
    }
    return colors;
}
