import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

import './modal';

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

window.FullCalendar = { 
    Calendar, 
    dayGridPlugin, 
    timeGridPlugin, 
    interactionPlugin 
};

Alpine.start();
