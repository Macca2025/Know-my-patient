// QR Code Display Page Logic (Full Implementation)
class QRCodeManager {
	constructor(uniqueCode) {
		this.countdownTimer = null;
		this.isHidden = false;
		this.timeLeft = 20;
		this.totalTime = 20;
		this.isRevealing = false;
		// DOM elements
		   this.elements = {
			   qrImage: document.getElementById('qrImage'),
			   uniqueCodeElement: document.getElementById('uniqueCode'),
			   qrBlurOverlay: document.getElementById('qrBlurOverlay'),
			   codeBlurOverlay: document.getElementById('codeBlurOverlay'),
			   qrContainer: document.getElementById('qrContainer'),
			   codeContainer: document.getElementById('codeContainer'),
			   revealBtn: document.getElementById('revealBtn'),
			   shareBtn: document.getElementById('shareBtn'),
			   timerDisplay: document.getElementById('timerDisplay'),
			   countdownElement: document.getElementById('countdown'),
			   timerProgressBar: document.getElementById('timerProgressBar'),
			   securityNotice: document.getElementById('securityNotice')
		   };
		this.uniqueCode = uniqueCode;
		this.init();
	}

	init() {
		this.addEventListeners();
		this.addClickToCopyFunctionality();
		this.addShareFunctionality();
		this.playEntranceAnimations();
		// Always hide overlays on page load
		if (this.elements.qrBlurOverlay) {
			this.elements.qrBlurOverlay.classList.add('d-none');
			this.elements.qrBlurOverlay.style.display = 'none';
		}
		if (this.elements.codeBlurOverlay) {
			this.elements.codeBlurOverlay.classList.add('d-none');
			this.elements.codeBlurOverlay.style.display = 'none';
		}
		// Hide reveal button if code is visible
		if (!this.isHidden && this.elements.revealBtn) {
			this.elements.revealBtn.style.display = 'none';
		}
		this.startCountdown();
	}
	playEntranceAnimations() {
		const elements = [
			this.elements.securityNotice,
			this.elements.qrContainer,
			this.elements.codeContainer
		];
		elements.forEach((element, index) => {
			if (element) {
				element.style.animationDelay = `${index * 0.3}s`;
			}
		});
	}
	startCountdown() {
		this.timeLeft = this.totalTime;
		this.updateCountdownDisplay();
		this.updateProgressBar();
		if (this.countdownTimer) clearInterval(this.countdownTimer);
		this.countdownTimer = setInterval(() => {
			this.timeLeft--;
			this.updateCountdownDisplay();
			this.updateProgressBar();
			if (this.timeLeft <= 5) {
				this.addWarningEffects();
			}
			if (this.timeLeft <= 0) {
				this.hideCode();
			}
		}, 1000);
	}
	updateCountdownDisplay() {
		// Always re-query the countdown and timer display elements in case DOM changed
		this.elements.countdownElement = document.getElementById('countdown');
		this.elements.timerDisplay = document.getElementById('timerDisplay');
		   if (this.elements.countdownElement) {
			   if (this.timeLeft > 0) {
				   this.elements.countdownElement.textContent = this.timeLeft;
				   if (this.timeLeft <= 5) {
					   this.elements.countdownElement.classList.add('countdown-warning');
				   } else {
					   this.elements.countdownElement.classList.remove('countdown-warning');
				   }
			   } else {
				   // Aggressively update the timer display message
				   if (this.elements.timerDisplay) {
					   this.elements.timerDisplay.innerHTML = `
						   <div class="timer-icon">
							   <i class="bi bi-shield-check-fill"></i>
						   </div>
						   <span class="timer-text security-hidden">Code is now hidden for security</span>
					   `;
				   }
			   }
		   }
	}
	updateProgressBar() {
		if (this.elements.timerProgressBar) {
			const percentage = (this.timeLeft / this.totalTime) * 100;
			this.elements.timerProgressBar.style.width = `${percentage}%`;
			if (this.timeLeft <= 5) {
				this.elements.timerProgressBar.style.background = 'linear-gradient(90deg, #ff6b6b, #ee5a52)';
			} else {
				this.elements.timerProgressBar.style.background = 'linear-gradient(90deg, #667eea, #764ba2)';
			}
		}
	}
	addWarningEffects() {
		if (this.elements.timerDisplay) {
			this.elements.timerDisplay.style.animation = 'countdownPulse 0.5s ease-in-out infinite alternate';
		}
		this.elements.qrContainer?.classList.add('warning-shake');
		this.elements.codeContainer?.classList.add('warning-shake');
	}
	async hideCode() {
		if (this.isHidden) return;
		clearInterval(this.countdownTimer);
		this.isHidden = true;
		await this.animateHide();
		this.updateUIForHiddenState();
	}
	async animateHide() {
		return new Promise(resolve => {
			this.elements.qrImage?.classList.add('blurred');
			if (this.elements.uniqueCodeElement) {
				this.elements.uniqueCodeElement.style.filter = 'blur(8px)';
				this.elements.uniqueCodeElement.style.transition = 'filter 0.5s ease';
			}
			setTimeout(() => {
				this.elements.qrBlurOverlay?.classList.remove('d-none');
				this.elements.codeBlurOverlay?.classList.remove('d-none');
				resolve();
			}, 300);
		});
	}
	updateUIForHiddenState() {
		if (this.elements.revealBtn) {
			this.elements.revealBtn.style.display = 'inline-block';
			this.elements.revealBtn.style.animation = 'fadeInUp 0.5s ease-out';
		}
		if (this.elements.timerDisplay) {
			this.elements.timerDisplay.innerHTML = `
				<div class="timer-icon">
					<i class="bi bi-shield-check-fill"></i>
				</div>
				<span class="timer-text security-hidden">Code is now hidden for security</span>
			`;
		}
		this.elements.securityNotice?.classList.add('d-none');
		this.elements.qrContainer?.classList.add('security-hidden');
		this.elements.codeContainer?.classList.add('security-hidden');
		if (this.elements.timerProgressBar) {
			this.elements.timerProgressBar.style.width = '0%';
		}
	}
	async revealCode() {
		if (!this.isHidden || this.isRevealing) return;
		this.isRevealing = true;
		if (window.hapticManager) {
			window.hapticManager.trigger('reveal');
		}
		await this.animateReveal();
		this.updateUIForRevealedState();
		this.startCountdown();
		this.isRevealing = false;
	}
	async animateReveal() {
		return new Promise(resolve => {
			this.elements.qrBlurOverlay?.classList.add('d-none');
			this.elements.codeBlurOverlay?.classList.add('d-none');
			this.elements.qrImage?.classList.remove('blurred');
			if (this.elements.uniqueCodeElement) {
				this.elements.uniqueCodeElement.style.filter = '';
				this.elements.uniqueCodeElement.style.transition = 'filter 0.5s ease';
			}
			this.addRevealGlowEffect();
			setTimeout(resolve, 500);
		});
	}
	addRevealGlowEffect() {
		[this.elements.qrContainer, this.elements.codeContainer].forEach(container => {
			if (container) {
				container.style.filter = 'drop-shadow(0 0 20px rgba(102,126,234,0.6))';
				setTimeout(() => {
					container.style.filter = '';
					container.style.transition = 'filter 1s ease-out';
				}, 1000);
			}
		});
	}
	updateUIForRevealedState() {
		this.isHidden = false;
		if (this.elements.revealBtn) {
			this.elements.revealBtn.style.display = 'none';
		}
		this.elements.securityNotice?.classList.remove('d-none');
		this.timeLeft = this.totalTime;
		if (this.elements.timerDisplay) {
			this.elements.timerDisplay.innerHTML = `
				<div class="timer-icon">
					<i class="bi bi-stopwatch"></i>
				</div>
				<span class="timer-text">Code will be hidden in <span class="countdown-number" id="countdown">${this.timeLeft}</span> seconds</span>
			`;
		}
		this.elements.countdownElement = document.getElementById('countdown');
		this.elements.qrContainer?.classList.remove('security-hidden');
		this.elements.codeContainer?.classList.remove('security-hidden');
		this.elements.qrContainer?.classList.remove('warning-shake');
		this.elements.codeContainer?.classList.remove('warning-shake');
	}
	addEventListeners() {
		this.elements.qrBlurOverlay?.addEventListener('click', (e) => {
			e.preventDefault();
			this.revealCode();
		});
		this.elements.codeBlurOverlay?.addEventListener('click', (e) => {
			e.preventDefault();
			this.revealCode();
		});
		this.elements.revealBtn?.addEventListener('click', (e) => {
			e.preventDefault();
			this.addButtonRippleEffect(e.target);
			this.revealCode();
		});
		document.addEventListener('keydown', (e) => {
			if (e.key === 'r' || e.key === 'R') {
				if (this.isHidden) {
					e.preventDefault();
					this.revealCode();
				}
			}
			if (e.key === 'Escape') {
				if (!this.isHidden) {
					e.preventDefault();
					this.hideCode();
				}
			}
			if (e.key === 'c' || e.key === 'C') {
				if (!this.isHidden) {
					e.preventDefault();
					this.copyCode();
				}
			}
		});
		document.addEventListener('visibilitychange', () => {
			if (document.hidden) {
				clearInterval(this.countdownTimer);
			} else if (!this.isHidden) {
				this.startCountdown();
			}
		});
	}
	addClickToCopyFunctionality() {
		if (this.elements.uniqueCodeElement) {
			this.elements.uniqueCodeElement.addEventListener('click', () => {
				if (!this.isHidden) {
					this.copyCode();
				}
			});
			this.elements.uniqueCodeElement.title = 'Click to copy code (C key)';
			this.elements.uniqueCodeElement.style.cursor = 'pointer';
		}
	}
	async copyCode() {
		try {
			await navigator.clipboard.writeText(this.uniqueCode);
			if (window.hapticManager) {
				window.hapticManager.trigger('success');
			}
			this.showCopySuccess();
		} catch (err) {
			this.fallbackCopyCode();
		}
	}
	fallbackCopyCode() {
		const textArea = document.createElement('textarea');
		textArea.value = this.uniqueCode;
		document.body.appendChild(textArea);
		textArea.select();
		try {
			document.execCommand('copy');
			this.showCopySuccess();
		} catch (err) {
			console.error('Failed to copy code:', err);
		}
		document.body.removeChild(textArea);
	}
	showCopySuccess() {
		const codeText = this.elements.uniqueCodeElement?.querySelector('.code-text');
		if (codeText) {
			const originalText = codeText.textContent;
			codeText.textContent = '✓ Copied!';
			codeText.style.background = 'rgba(40, 167, 69, 0.2)';
			codeText.style.borderColor = '#28a745';
			codeText.style.color = '#28a745';
			this.elements.uniqueCodeElement?.classList.add('copy-success');
			setTimeout(() => {
				codeText.textContent = originalText;
				codeText.style.background = '';
				codeText.style.borderColor = '';
				codeText.style.color = '';
				this.elements.uniqueCodeElement?.classList.remove('copy-success');
			}, 2000);
		}
	}
	addShareFunctionality() {
		this.elements.shareBtn?.addEventListener('click', async (e) => {
			e.preventDefault();
			this.addButtonRippleEffect(e.target);
			if (navigator.share) {
				try {
					await navigator.share({
						title: 'My Medical QR Code',
						text: `My personal medical code: ${this.uniqueCode}`,
						url: window.location.href
					});
				} catch (err) {
					if (err.name !== 'AbortError') {
						this.fallbackShare();
					}
				}
			} else {
				this.fallbackShare();
			}
		});
	}
	fallbackShare() {
		const shareText = `My personal medical code: ${this.uniqueCode}\nURL: ${window.location.href}`;
		navigator.clipboard.writeText(shareText).then(() => {
			if (window.hapticManager) {
				window.hapticManager.trigger('success');
			}
			this.showShareSuccess();
		}).catch(() => {
			if (window.hapticManager) {
				window.hapticManager.trigger('error');
			}
			alert(`Share this information:\n\n${shareText}`);
		});
	}
	showShareSuccess() {
		const originalContent = this.elements.shareBtn?.innerHTML;
		if (this.elements.shareBtn && originalContent) {
			this.elements.shareBtn.innerHTML = '<span class="btn-content"><i class="bi bi-check me-2"></i>Copied!</span>';
			this.elements.shareBtn.style.background = 'rgba(40, 167, 69, 0.3)';
			setTimeout(() => {
				this.elements.shareBtn.innerHTML = originalContent;
				this.elements.shareBtn.style.background = '';
			}, 2000);
		}
	}
	addButtonRippleEffect(button) {
		const ripple = button.querySelector('.btn-ripple');
		if (ripple) {
			ripple.style.width = '300px';
			ripple.style.height = '300px';
			setTimeout(() => {
				ripple.style.width = '0';
				ripple.style.height = '0';
			}, 500);
		}
	}
}

// Only initialize QRCodeManager on the display page
document.addEventListener('DOMContentLoaded', () => {
	const uniqueCodeEl = document.getElementById('uniqueCode');
	if (uniqueCodeEl) {
		// Get the code from the element's text content
		const code = uniqueCodeEl.textContent.trim();
		window.qrManager = new QRCodeManager(code);
		// Add warning shake animation CSS
		const style = document.createElement('style');
		style.textContent = `
			.warning-shake { animation: warningShake 0.5s ease-in-out infinite alternate; }
			@keyframes warningShake { 0% { transform: translateX(0); } 100% { transform: translateX(2px); } }
		`;
		document.head.appendChild(style);
	}
});
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

		let hasPlayed = false;
		const videoContainer = video.closest('.video-container');
		video.addEventListener('play', function() {
			playIcon.classList.remove('bi-play-fill');
			playIcon.classList.add('bi-pause-fill');
			videoOverlay.style.display = 'none';
			playBtn.style.display = 'none';
			video.style.opacity = '1';
			video.style.filter = 'none';
			hasPlayed = true;
			videoOverlay.classList.add('no-blur');
			if (videoContainer) videoContainer.classList.add('has-played');
		});

		video.addEventListener('pause', function() {
			playIcon.classList.remove('bi-pause-fill');
			playIcon.classList.add('bi-play-fill');
			if (!hasPlayed) {
				videoOverlay.style.display = 'flex';
				playBtn.style.display = 'block';
			} else {
				videoOverlay.style.display = 'none';
				playBtn.style.display = 'none';
				videoOverlay.classList.add('no-blur');
			}
			video.style.opacity = '1';
			video.style.filter = 'none';
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

	
	// Password visibility toggle for login/register
	const passwordInput = document.getElementById('password');
	const togglePasswordBtn = document.getElementById('togglePassword');
	const togglePasswordIcon = document.getElementById('togglePasswordIcon');
	if (passwordInput && togglePasswordBtn && togglePasswordIcon) {
		togglePasswordBtn.addEventListener('click', function() {
			const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
			passwordInput.setAttribute('type', type);
			togglePasswordIcon.classList.toggle('bi-eye');
			togglePasswordIcon.classList.toggle('bi-eye-slash');
		});
	}
});
