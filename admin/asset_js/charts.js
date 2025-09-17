// charts.js
const ctx = document.getElementById('bookingChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: bookingAnalytics.map(item => item.event_package),
        datasets: [{
            label: 'Number of Bookings',
            data: bookingAnalytics.map(item => item.booking_count),
            backgroundColor: [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0',
                '#9966FF',
                '#FF9F40'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true
            }
        }
    }
});

// Monthly Trend Line Chart
const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('default', { month: 'long', year: 'numeric' });
        }),
        datasets: [{
            label: 'Number of Bookings',
            data: monthlyData.map(item => item.booking_count),
            borderColor: '#36A2EB',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Monthly Booking Trends',
                font: {
                    size: 24, // H3 is usually around 24px
                    weight: 'bold' // Makes it bold like H3
                },
                color: '#333', // Optional: Adjust text color
                padding: {
                    top: 15, // Space above the title
                    bottom: 15 // Space between title and chart
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Function to fetch event types and counts from the server
async function fetchEventData() {
    try {
        const response = await fetch('get_event_data.php');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return await response.json();
    } catch (error) {
        console.error('Error fetching event data:', error);
        return null;
    }
}

// Function to draw the event pie chart
function drawEventChart(data) {
    const eventCtx = document.getElementById('eventBookingsChart').getContext('2d');
    
    const colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40',
        '#C9CBCF', '#7BC043', '#F37735', '#D11141', '#8A9B0F', '#00A8C6'
    ];
    
    if (window.eventChart) {
        window.eventChart.destroy();
    }
    
    window.eventChart = new Chart(eventCtx, {
        type: 'pie',
        data: {
            labels: data.map(item => item.event_package),
            datasets: [{
                data: data.map(item => item.count),
                backgroundColor: colors.slice(0, data.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Event Bookings'
                }
            }
        }
    });
}

// Month navigation functionality
let currentDisplayMonth = new Date();

function updateMonthDisplay() {
    document.getElementById('currentMonthDisplay').textContent =
        currentDisplayMonth.toLocaleDateString('default', { month: 'long', year: 'numeric' });
}

function fetchMonthData(month, year) {
    fetch(`get_month_data.php?month=${month + 1}&year=${year}`)
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                const data = response.data;

                // Update statistics
                document.getElementById('totalBookings').textContent = data.total_bookings;
                document.getElementById('confirmedBookings').textContent = data.confirmed_bookings;
                document.getElementById('pendingBookings').textContent = data.pending_bookings;

                // Format popular packages
                const packagesText = data.popular_packages.length > 0
                    ? data.popular_packages.join(', ')
                    : 'No bookings';
                document.getElementById('popularPackages').textContent = packagesText;

                // Optional: Add visual feedback for the update
                const statCards = document.querySelectorAll('.stat-card');
                statCards.forEach(card => {
                    card.style.transition = 'transform 0.3s ease';
                    card.style.transform = 'scale(1.05)';
                    setTimeout(() => {
                        card.style.transform = 'scale(1)';
                    }, 300);
                });
            } else {
                console.error('Failed to fetch month data:', response.message);
            }
        })
        .catch(error => {
            console.error('Error fetching month data:', error);
        });
}

// Event listeners for navigation buttons
document.getElementById('prevMonth').addEventListener('click', () => {
    currentDisplayMonth.setMonth(currentDisplayMonth.getMonth() - 1);
    updateMonthDisplay();
    fetchMonthData(currentDisplayMonth.getMonth(), currentDisplayMonth.getFullYear());
});

document.getElementById('nextMonth').addEventListener('click', () => {
    currentDisplayMonth.setMonth(currentDisplayMonth.getMonth() + 1);
    updateMonthDisplay();
    fetchMonthData(currentDisplayMonth.getMonth(), currentDisplayMonth.getFullYear());
});

// Initialize all charts when the page loads
document.addEventListener('DOMContentLoaded', async () => {
    // Initialize existing monthly display and data
    updateMonthDisplay();
    fetchMonthData(currentDisplayMonth.getMonth(), currentDisplayMonth.getFullYear());
    
    // Initialize new event chart
    const eventData = await fetchEventData();
    if (eventData) {
        drawEventChart(eventData);
    }
});