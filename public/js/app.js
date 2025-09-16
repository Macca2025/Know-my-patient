// Scroll to first error or form after onboarding submit 
document.addEventListener('DOMContentLoaded', function() {
	// Prevent form resubmission on refresh or back navigation
	var onboardingForm = document.getElementById('onboardingForm');
	if (onboardingForm) {
		if (window.history && window.history.replaceState) {
			window.history.replaceState(null, '', window.location.href);
		}
	}

	var form = document.getElementById('onboardingForm');
	if (form) {
		// If there are server-side errors, scroll to the first error
		var errorField = document.querySelector('.invalid-feedback.d-block');
		if (errorField) {
			errorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
		} else {
			// If there is a success message, scroll to the form
			var successAlert = document.querySelector('.alert-success');
			if (successAlert) {
				form.scrollIntoView({ behavior: 'smooth', block: 'center' });
			} else if (window.location.hash === '#enquiry-form') {
				var formSection = document.getElementById('enquiry-form');
				if (formSection) {
					formSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
				}
			}
		}
	}
});


document.addEventListener('DOMContentLoaded', function() {
	// Play/Pause video logic
	const video = document.getElementById('intro-video');
	const playBtn = document.getElementById('play-pause-btn');
	const playIcon = document.getElementById('play-icon');
	const videoOverlay = document.getElementById('video-overlay');

	if (video && playBtn && playIcon && videoOverlay) {
		playBtn.addEventListener('click', function() {
			if (video.paused) {
				video.play();
			} else {
				video.pause();
			}
		});

		video.addEventListener('play', function() {
			playIcon.classList.remove('bi-play-fill');
			playIcon.classList.add('bi-pause-fill');
			videoOverlay.style.display = 'none';
			playBtn.style.display = 'none';
			video.style.opacity = '1';
			video.style.filter = 'none';
		});

		video.addEventListener('pause', function() {
			playIcon.classList.remove('bi-pause-fill');
			playIcon.classList.add('bi-play-fill');
			videoOverlay.style.display = 'flex';
			playBtn.style.display = 'block';
			video.style.opacity = '0.5';
			video.style.filter = 'blur(2px)';
		});
	}

	
	// Testimonial slider logic
	const testimonials = document.querySelectorAll('.testimonial-card');
	let current = 0;

	function showTestimonial(index) {
		testimonials.forEach((card, i) => {
			card.classList.remove('active');
			card.style.display = 'none';
		});
		testimonials[index].classList.add('active');
		testimonials[index].style.display = 'block';
	}

	function nextTestimonial() {
		testimonials[current].classList.remove('active');
		testimonials[current].style.display = 'none';
		current = (current + 1) % testimonials.length;
		testimonials[current].classList.add('active');
		testimonials[current].style.display = 'block';
	}

	if (testimonials.length > 0) {
		showTestimonial(current);
		setInterval(nextTestimonial, 5000);
	}

});
