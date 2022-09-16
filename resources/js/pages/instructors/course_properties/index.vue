<template>
  <div class="row">
    <div v-if="user.role === 2" class="col-md-3">
      <b-card header-html="<h2 class=&quot;h7&quot;>Course Properties</h2>" class="properties-card">
        <ul class="nav flex-column nav-pills">
          <li v-for="tab in tabs" :key="tab.route" class="nav-item">
            <router-link :to="{ name: tab.route }" class="nav-link" active-class="active">
              <span class="hover-underline">{{ tab.name }}</span>
            </router-link>
          </li>
          <li>
            <a :href="`/courses/${courseId}/gradebook`" class="nav-link">
              <span class="hover-underline">Gradebook</span>
            </a>
          </li>
          <li class="nav-item">
            <router-link :to="{ name: 'course_properties.reset_course' }" class="nav-link" active-class="active">
              <span class="hover-underline">Reset Course</span>
            </router-link>
          </li>
        </ul>
      </b-card>
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
          name: 'A11y Redirect',
          route: 'course_properties.a11y_redirect'
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
        },
        {
          icon: '',
          name: 'Testers',
          route: 'course_properties.testers'
        }
      ]
    }
  },
  created () {
    this.courseId = this.$route.params.courseId
  },
  mounted () {
    if (![2, 5].includes(this.user.role)) {
      this.$router.push({ name: 'no.access' })
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
