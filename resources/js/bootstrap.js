import axios from "axios";
import juice from "../../vendor/keystone/admin/resources/js/juice/juice.js";
import "../../vendor/keystone/admin/resources/js/juice/forms/index.mjs";

//Setup Juice
juice.expose();

//Setup Axios
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
