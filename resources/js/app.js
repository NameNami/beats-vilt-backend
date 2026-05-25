import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue, route } from 'ziggy-js';

window.route = route;

// import { library } from '@fortawesome/fontawesome-svg-core';
// import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
// import { faUser } from '@fortawesome/free-solid-svg-icons'; // Example of specific icon import

// library.add(faUser);

createInertiaApp({
    title: (title) => `${title} - BEATS`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            // .component('font-awesome-icon', FontAwesomeIcon)
            .mount(el)
    },
})
