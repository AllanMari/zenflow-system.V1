import './bootstrap';
import '../css/app.css';

// Make globally available
window.Calendar = Calendar;
window.FullCalendarPlugins = {
    timeGrid: timeGridPlugin,
    dayGrid: dayGridPlugin,
    interaction: interactionPlugin
};