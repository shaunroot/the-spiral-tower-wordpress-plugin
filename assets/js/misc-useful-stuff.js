// // Show/Hide content
// document.addEventListener('DOMContentLoaded', function() {
//     // Get our elements
//     const title = document.querySelector('.spiral-tower-floor-title');
//     const container = document.querySelector('.spiral-tower-floor-container');
    
//     // Initial position - off screen
//     container.style.transform = 'translateY(-100vh)';
//     container.style.transition = 'transform 0.4s ease';
    
//     // Show content on hover
//     title.addEventListener('mouseenter', function() {
//       container.style.transform = 'translateY(0)';
//     });
    
//     // Hide when mouse leaves both elements
//     title.addEventListener('mouseleave', function() {
//       if (!container.matches(':hover')) {
//         container.style.transform = 'translateY(-100vh)';
//       }
//     });
    
//     container.addEventListener('mouseleave', function() {
//       if (!title.matches(':hover')) {
//         container.style.transform = 'translateY(-100vh)';
//       }
//     });
//   });

