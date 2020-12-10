<template>
  <div class="row">
    <div v-if="user.role === 2" class="col-md-3">
      <card title="Course Properties" class="properties-card">
        <ul class="nav flex-column nav-pills">
          <li v-for="tab in tabs" :key="tab.route" class="nav-item">
            <router-link :to="{ name: tab.route }" class="nav-link" active-class="active">
              {{ tab.name }}
            </router-link>
          </li>
        </ul>
      </card>
    </div>

    <div class="col-md-9">
      <transition name="fade" mode="out-in">
        <router-view />
      </transition>
    </div>
  </div>
</template>

<script>

import { mapGetters } from 'vuex'
export default {
  middleware: 'auth',
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    tabs () {
      return [
        {
          icon: '',
          name: 'General Information',
          route: 'course_properties.general_info'
        },
        {
          icon: '',
          name: 'People',
          route: 'course_properties.graders'
        },
        {
          icon: '',
          name: 'Letter Grades',
          route: 'course_properties.letter_grades'
        },
        {
          icon: '',
          name: 'Refresh Access Code',
          route: 'course_properties.access_codes'
        },
        {
          icon: '',
          name: 'Assignment Groups',
          route: 'course_properties.assignment_groups'
        }
      ]
    }
  },
  mounted () {
    if (this.user.role !== 2) {
      this.$noty.error('You not have access to the course properties page.')
    }
  }
}
</script>

<style>
.properties-card .card-body {
  padding: 0;
}
</style>
