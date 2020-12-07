<template>
  <div class="row">
    <div class="col-md-3">
      <card :title="$t('Properties')" class="properties-card">
        <ul class="nav flex-column nav-pills">
          <li v-for="tab in tabs" :key="tab.route" class="nav-item">
            <router-link :to="{ name: tab.route }" class="nav-link" active-class="active">
              <fa :icon="tab.icon" fixed-width />
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
export default {
  middleware: 'auth',

  computed: {
    tabs () {
      return [
        {
          icon: 'user',
          name: 'Assignment Groups',
          route: 'course_properties.assignment_groups'
        },
        {
          icon: 'lock',
          name: 'Letter Grades',
          route: 'course_properties.letter_grades'
        },
        {
          icon: 'lock',
          name: 'Access Codes',
          route: 'course_properties.access_codes'
        },
        {
          icon: 'lock',
          name: 'Graders',
          route: 'course_properties.graders'
        }
      ]
    }
  }
}
</script>

<style>
.properties-card .card-body {
  padding: 0;
}
</style>
