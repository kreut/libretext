<template>
  <div class="main-layout">
    <navbar />

    <div class="mt-4" style="min-height: 700px">
      <child />
    </div>

  <div class="d-flex flex-column" style="background: #e5f5fe">
    <footer class="footer" style="border:1px solid #30b3f6">
      <p class="pt-3 pl-3 pr-4">The LibreTexts Adapt platform is supported by the Department of Education Open Textbook Pilot Project and the <a href="https://opr.ca.gov/learninglab/">California Education Learning Lab</a>.
        Unless otherwise noted, LibreTexts content is licensed by CC BY-NC-SA 3.0. Have questions or comments? For more information contact us at <a href="mailto:info@libretexts.org.">info@libretexts.org.</a></p>

      <div class="d-flex  justify-content-center flex-wrap">
        <a class="ml-5 pt-3 pb-3" href="https://www.ed.gov/news/press-releases/us-department-education-awards-49-million-grant-university-california-davis-develop-free-open-textbooks-program" rel="external nofollow" target="_blank" title="https://www.ed.gov/news/press-releases/us-department-education-awards-49-million-grant-university-california-davis-develop-free-open-textbooks-program"> <img alt="DOE Logo.png"  src="/assets/img/DOE.png" /></a>
        <a class="ml-5 pt-3 pb-3" href="https://blog.libretexts.org/2020/03/21/libretext-project-announces-1-million-california/" rel="external nofollow" target="_blank" title="https://blog.libretexts.org/2020/03/21/libretext-project-announces-1-million-california/"> <img alt="DOE Logo.png" style="height:85px;"  src="/assets/img/CELL_LogoColor.png" /></a>   </div>
    </footer>
  </div>
  </div>
</template>

<script>
import Navbar from '~/components/Navbar'


export default {
  name: 'Remediations',
  components: {
    Navbar,
  }
}
</script>

