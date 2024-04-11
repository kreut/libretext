<template>
  <div>
    <b-container class="mb-5">
      <b-row>
        <b-col sm="12" md="4" lg="6">
          <b-img src="https://cdn.libretexts.net/Images/adapt-welcome-1.jpg" fluid role="presentation" alt=""></b-img>
        </b-col>
        <b-col sm="12" md="8" lg="6" class="align-self-center mt-4 mt-md-0">
          <h1>Welcome to ADAPT</h1>
          <p class="lead">ADAPT provides a unique blend of pre-built, high-quality assessments alongside personalized learning models, offering limitless customization options to ensure that your content aligns perfectly with your curriculum needs.</p>
          <b-button size="lg" variant="info" @click="$router.push({ path: '/open-courses/commons' })">
            Browse Open Courses
          </b-button>
          <b-link href="https://libretexts.org/platforms/adapt" class="btn btn-lg btn-outline-light" target="_blank">
            Learn More
          </b-link>
        </b-col>
      </b-row>
    </b-container>

    <b-container class="mt-5">
      <b-row>
        <b-col sm="12" md="6">
          <div>
            <b-card no-body class="overflow-hidden" style="max-width: 540px;">
              <b-row no-gutters>
                <!-- <b-col md="3">
                  <b-card-img src="/assets/img/adapt-student-welcome.jpg" alt="Image" class="rounded-0"></b-card-img>
                </b-col> -->
                <b-col md="12">
                  <b-card-body title="Students">
                    <b-card-text>
                      Create your Student account to enroll your course.
                    </b-card-text>
                    <b-button variant="dark" @click="$router.push({ path: '/register/student' })">
                      Create your Student Account
                    </b-button>
                  </b-card-body>
                </b-col>
              </b-row>
            </b-card>
          </div>
        </b-col>
        <div class="spacer" style="height: 50px;"></div>
        <b-col sm="12" md="6">
          <div>
            <b-card no-body class="overflow-hidden" style="max-width: 540px;">
              <b-row no-gutters>
                <!-- <b-col md="3">
                  <b-card-img src="/assets/img/adapt_welcome_instructor.jpg" alt="Image" class="rounded-0"></b-card-img>
                </b-col> -->
                <b-col md="12">
                  <b-card-body title="Instructors">
                    <b-card-text>
                      Create your Instructor account to enroll your course.
                    </b-card-text>
                    <b-button variant="dark" @click="$router.push({ path: '/register/instructor' })">
                      Create your Instructor Account
                    </b-button>
                  </b-card-body>
                </b-col>
              </b-row>
            </b-card>
          </div>
        </b-col>
      </b-row>
    </b-container>

    <b-container class="mt-5 d-none">
      <b-row>
        <b-col>
          <div class="d-flex justify-content-center align-items-baseline" style="gap: 0.5em">
            <p class="font-weight-bold mx-1">Register with other account types:</p>
            <b-button variant="sm btn-outline-secondary" @click="$router.push({ path: '/register/grader' })">
              Grader Account
            </b-button>
            <b-button variant="sm btn-outline-secondary" @click="$router.push({ path: '/register/question-editor' })">
              Non-Instructor Editor Account
            </b-button>
            <b-button variant="sm btn-outline-secondary" @click="$router.push({ path: '/register/tester' })">
              Tester Account
            </b-button>
          </div>
        </b-col>
      </b-row>
    </b-container>

    <!-- <div class="text-center">
      <h1 class="title text-primary" style="margin-top:100px">
        {{ title }}
      </h1>
      <div class="h5 homepage-subtitle pb-5">
        The Libretexts Adaptive Learning Assessment System
      </div>
      <b-button size="lg" variant="info" @click="$router.push({path:'/open-courses/commons'})">
        Commons
      </b-button>
    </div> -->
  </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {

  metaInfo() {
    return { title: 'Home' }
  },

  data: () => ({
    backgroundUrl: '',
    title: window.config.appName
  }),
  computed: mapGetters({
    authenticated: 'auth/check',
    user: 'auth/user'
  }),
  mounted() {
    if (this.authenticated && this.user.is_tester_student) {
      this.$router.push({ name: 'cannot.view.as.testing.student' })
    }
    this.resizeHandler()
    window.addEventListener('resize', this.resizeHandler)
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.resizeHandler)
  },
  methods: {
    resizeHandler() {
      let basicLayout = document.getElementsByClassName('basic-layout')
      if (basicLayout.length) {
        this.zoomGreaterThan(1.5)
          ? basicLayout[0].style.height = 'auto'
          : basicLayout[0].style.height = '100vh'
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.bg {
  //background-image: url("~@/assets/img/hatice-yardim-5LvC-QX0OOc-unsplash.jpg");
  opacity: 0.6;
  height: 100%;
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
}

.top-right {
  position: absolute;
  right: 10px;
  top: 18px;
}

.title {
  font-size: 85px;
}

.basic-layout {
  color: #636b6f;
  height: 100vh;
  font-weight: 100;
  position: relative;
  padding-top: 100px;

  .links>a {
    color: #636b6f;
    padding: 0 25px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .1rem;
    text-decoration: none;
    text-transform: uppercase;
  }
}
</style>
