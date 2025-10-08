
import { createApp } from "vue";

import App from "./App.vue";
import "./style.css"; 
// import "../src/assets/css/UserDetailsModal.css"
import { createPinia } from "pinia";
import router from "./router";
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue-3/dist/bootstrap-vue-3.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import 'bootstrap-icons/font/bootstrap-icons.css'

import BootstrapVue3 from 'bootstrap-vue-3'

const app = createApp(App);
app.use(BootstrapVue3)
app.use(createPinia());
app.use(router);
app.mount("#app");

