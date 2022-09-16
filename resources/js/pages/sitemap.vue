<template>
  <div>
    <PageTitle title="Sitemap"/>
    <b-card header="default" header-html="<h2 class=&quot;h7&quot;>General Information</h2>" class="mb-3">
      <b-card-text>
        <ul>
          <li>
            <router-link :to="{path: '/'}">
              Homepage
            </router-link>
          </li>
          <li>
            <router-link :to="{name: 'login'}">
              Login
            </router-link>
          </li>
          <li>
            <router-link :to="{name: 'commons'}">
              Commons
            </router-link>
          </li>
          <li>
            <router-link to="/register/student">
              Register - Student
            </router-link>
          </li>
          <li>
            <router-link to="/register/instructor">
              Register - Instructor
            </router-link>
          </li>
          <li>
            <router-link to="/register/grader">
              Register - Grader
            </router-link>
          </li>
          <li>
            <router-link :to="{name: 'question-editor-register'}">
              Register - Non-Instructor Editor
            </router-link>
          </li>
          <li>
            <router-link :to="{name: 'question-editor-register'}">
              Register - Non-Instructor Editor
            </router-link>
          </li>
        </ul>
      </b-card-text>
    </b-card>

  </div>
</template>

<script>

export default {
  metaInfo () {
    return { title: 'Sitemap' }
  }
}
</script>

<style scoped>

</style>
