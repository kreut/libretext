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
          <li>
          <a :href="`/courses/${courseId}/gradebook`" class="nav-link">
            Gradebook
          </a>
          </li>
        </ul>
      </card>
    </div>

    <div class="col-md-9">
      <transition name="fade" mode="out-in">
        <router-view/>
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
    data: () => ({
      lms: false,
      courseId: 0
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
          name: 'Sections',
          route: 'course_properties.sections'
        },
        {
          icon: '',
          name: 'Graders',
          route: 'course_properties.graders'
        },
        {
          icon: '',
          name: 'Grader Notifications',
          route: 'course_properties.grader_notifications'
        },
        {
          icon: '',
          name: 'Students',
          route: 'course_properties.students'
        },
        {
          icon: '',
          name: 'Tethered Courses',
          route: 'course_properties.tethered_courses'
        },
        {
          icon: '',
          name: 'Assignment Group Weights',
          route: 'course_properties.assignment_group_weights'
        },
        {
          icon: '',
          name: 'Letter Grades',
          route: 'course_properties.letter_grades'
        },
        {
          icon: '',
          name: 'Ungraded Submissions',
          route: 'course_properties.ungraded_submissions'
        },
        {
          icon: '',
          name: 'Embed Properties',
          route: 'course_properties.iframe_properties'
        }
      ]
    }
  },
  created () {
    this.courseId = this.$route.params.courseId
  },
  mounted () {
    if (this.user.role !== 2) {
      this.$noty.error('You do not have access to this page.')
      return false
    }
  }
}
</script>

<style>
.properties-card .card-body {
  padding: 0;
}
</style>
