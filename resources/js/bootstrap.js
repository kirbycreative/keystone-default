import axios from "axios";
import juice from "./vendor/juice/juice.js";

//Setup Juice
juice.expose();
juice.import("forms");

//Setup Axios
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
