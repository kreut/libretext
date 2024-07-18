<template>
  <span class="initials-circle" :style="isUser ? 'background-color: #0056b3;' : 'background-color: #007bff;'">
    {{ initials }}
  </span>
</template>

<script>
export default {
  name: 'UserInitials',
  props: {
    userName: {
      type: String,
      default: ''
    },
    isUser: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    initials () {
      return this.userName.split(' ').map(name => name[0]).join('').toUpperCase()
    }
  }
}
</script>

<style scoped>
.initials-circle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px; /* Adjusted to be smaller */
  height: 30px; /* Adjusted to be smaller */
  color: #fff;
  border-radius: 50%;
  font-size: 12px; /* Small font size */
  font-weight: bold;
  text-transform: uppercase;
}
</style>
