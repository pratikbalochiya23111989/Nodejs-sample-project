var dashboard = require('../../controllers/admin/dashboard.server.controller.js');
	
module.exports = function(app) {
	app.get('/admin/dashboard/list', dashboard.list);
	app.get('/admin/dashboard/loadDashboardChart', dashboard.ajax_loadchart);
};