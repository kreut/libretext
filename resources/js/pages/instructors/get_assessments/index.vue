<template>
  <div class="row">
    <div v-if="user.role === 2" class="col-md-3">
      <card title="Get Assessments" class="properties-card">
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
          name: 'Questions',
          route: 'questions.get'
        },
        {
          icon: '',
          name: 'Learning Trees',
          route: 'learning_trees.get'
        }
      ]
    }
  },
  mounted () {
    if (this.user.role !== 2) {
      this.$noty.error('You not have access to the get assessments page.')
    }
  }
}
</script>

<style>
.properties-card .card-body {
  padding: 0;
}
</style>
