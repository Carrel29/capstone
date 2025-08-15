class Calendar {
    constructor() {
        this.currentDate = new Date();
        this.events = [];
        this.init();
    }

    async init() {
        await this.loadEvents();
        this.render();
        this.attachEventListeners();
    }

    async loadEvents() {
        try {
            const response = await fetch(`?year=${this.currentDate.getFullYear()}&month=${this.currentDate.getMonth() + 1}`);
            this.events = await response.json();
        } catch (error) {
            console.error('Error loading events:', error);
            this.events = [];
        }
    }

    render() {
        const calendar = document.querySelector('.calendar');
        calendar.innerHTML = `
            <div class="calendar-header">
                <button onclick="calendar.previousMonth()">&lt;</button>
                <h2>${this.currentDate.toLocaleDateString('default', { month: 'long', year: 'numeric' })}</h2>
                <button onclick="calendar.nextMonth()">&gt;</button>
            </div>
            <div class="calendar-grid">
                ${this.renderDays()}
            </div>
        `;
        this.attachEventListeners();
    }

    renderDays() {
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        let html = days.map(day => `<div class="calendar-header-cell">${day}</div>`).join('');
        
        const firstDay = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
        const lastDay = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 0);
        
        for (let i = 0; i < firstDay.getDay(); i++) {
            html += '<div class="calendar-day empty"></div>';
        }

        for (let day = 1; day <= lastDay.getDate(); day++) {
            const date = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), day);
            const dateString = date.toISOString().split('T')[0];
            const dayEvents = this.events.filter(event => event.event_date.startsWith(dateString));
            
            // Group events by time slot and location
            const slots = {
                Morning: { onsite: 0, custom: 0 },
                Afternoon: { onsite: 0, custom: 0 },
                Evening: { onsite: 0, custom: 0 }
            };
            
            dayEvents.forEach(event => {
                const slot = event.time_slot;
                const type = event.location_type === 'On-site' ? 'onsite' : 'custom';
                if (slots[slot]) slots[slot][type]++;
            });
            
            // Determine cell color
            let cellClass = this.getCellColor(slots);
            
            html += `
                <div class="calendar-day ${cellClass}" data-date="${dateString}">
                    <div class="day-number">${day}</div>
                    <div class="event-dots">
                        ${dayEvents.map(event => `
                            <div class="event-dot" 
                                 title="${event.event_package} - ${event.time_slot}"
                                 style="background: ${event.location_type === 'On-site' ? '#007bff' : '#28a745'}">
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        return html;
    }

    getCellColor(slots) {
        let hasBookings = false;
        let hasConflict = false;
        let hasMultipleBookings = false;
        
        Object.values(slots).forEach(timeSlot => {
            // Check if there are any bookings in this time slot
            if (timeSlot.onsite > 0 || timeSlot.custom > 0) {
                hasBookings = true;
            }
            // Check for conflicts
            if (timeSlot.onsite > 1) {
                hasConflict = true;
            }
            // Check for multiple bookings
            if (timeSlot.onsite === 1 && timeSlot.custom > 0) {
                hasMultipleBookings = true;
            }
        });
        
        if (hasConflict) return 'red';
        if (hasMultipleBookings) return 'orange';
        if (hasBookings) return 'green';
        return ''; // Return empty string for no bookings
    }

    attachEventListeners() {
        document.querySelectorAll('.calendar-day:not(.empty)').forEach(day => {
            day.addEventListener('click', async () => {
                const date = day.dataset.date;
                if (date) {
                    const response = await fetch(`?date=${date}`);
                    const events = await response.json();
                    this.showEvents(date, events);
                }
            });
        });
    }

    showEvents(date, events) {
        const modal = document.getElementById('eventModal');
        const dateDisplay = document.getElementById('selectedDate');
        const eventsList = document.getElementById('eventsList');
        
        dateDisplay.textContent = new Date(date).toLocaleDateString();
        eventsList.innerHTML = events.length ? events.map(event => `
            <div class="event-item">
                <h3>${event.event_package}</h3>
                <p>Customer: ${event.customer_name}</p>
                <p>Status: ${event.status}</p>
                <p>Contact: ${event.contact_email} | ${event.contact_phone}</p>
                ${event.additional_details ? `<p>Details: ${event.additional_details}</p>` : ''}
                <p>Total Cost: ₱${event.total_cost}</p>
            </div>
        `).join('') : '<p>No events for this day</p>';
        
        modal.style.display = 'block';
    }

    async previousMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() - 1);
        await this.init();
    }

    async nextMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() + 1);
        await this.init();
    }
}

function closeModal() {
    document.getElementById('eventModal').style.display = 'none';
}

const calendar = new Calendar();

window.onclick = function(event) {
    const modal = document.getElementById('eventModal');
    if (event.target === modal) {
        closeModal();
    }
}
