<template>
  <div>
    <PageTitle title="Settings"/>
    <div class="row">
      <div class="col-md-3">
        <b-card class="settings-card">
          <ul class="nav flex-column nav-pills">
            <li v-for="tab in tabs" :key="tab.route" class="nav-item">
              <router-link :to="{ name: tab.route }" class="nav-link" active-class="active">
                {{ tab.name }}
              </router-link>
            </li>
            <li>
              <router-link v-if="user.role === 3" :to="{ name: 'settings.notifications' }" class="nav-link"
                           active-class="active"
              >
                Notifications
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
          name: this.$t('profile'),
          route: 'settings.profile'
        },
        {
          name: this.$t('password'),
          route: 'settings.password'
        }
      ]
    }
  }
}
</script>

<style>
.settings-card .card-body {
  padding: 0;
}
</style>
