"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.redirectOnLogin = redirectOnLogin;
exports.formatDate = void 0;

var formatDate = function formatDate(value) {
  var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  var date = new Date(value.replace(' ', 'T'));
  return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear();
};

exports.formatDate = formatDate;

function redirectOnLogin(store, router) {
  var userTypes = {
    2: 'instructors',
    3: 'students'
  };
  var role = userTypes[store.getters['auth/user'].role];
  router.push({
    name: "".concat(role, ".courses.index")
  });
}
