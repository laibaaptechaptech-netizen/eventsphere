// assets/js/three_hero.js - Interactive 3D Canvas Background using Three.js

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('hero-3d-canvas');
    if (!container || typeof THREE === 'undefined') return;

    let scene, camera, renderer, particles, sphereMesh, ringMesh;
    let mouseX = 0, mouseY = 0;
    let targetX = 0, targetY = 0;
    const windowHalfX = window.innerWidth / 2;
    const windowHalfY = window.innerHeight / 2;

    function init() {
        // 1. Scene setup
        scene = new THREE.Scene();
        scene.fog = new THREE.FogExp2(0x070913, 0.0015);

        // 2. Camera setup
        camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 1, 2000);
        camera.position.z = 600;

        // 3. Renderer setup
        renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.setSize(window.innerWidth, window.innerHeight);
        container.appendChild(renderer.domElement);

        // 4. Central Glowing Holographic EventSphere (Icosahedron + Wireframe)
        const sphereGeo = new THREE.IcosahedronGeometry(130, 2);
        const sphereMat = new THREE.MeshBasicMaterial({
            color: 0xa855f7,
            wireframe: true,
            transparent: true,
            opacity: 0.35
        });
        sphereMesh = new THREE.Mesh(sphereGeo, sphereMat);
        scene.add(sphereMesh);

        // 5. Glowing Outer Cyber Ring
        const ringGeo = new THREE.TorusGeometry(180, 2.5, 16, 100);
        const ringMat = new THREE.MeshBasicMaterial({
            color: 0x06b6d4,
            wireframe: true,
            transparent: true,
            opacity: 0.4
        });
        ringMesh = new THREE.Mesh(ringGeo, ringMat);
        ringMesh.rotation.x = Math.PI / 3;
        scene.add(ringMesh);

        // 6. Dynamic Orbiting Particle Cloud (1,500 particles)
        const particleCount = 1200;
        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(particleCount * 3);
        const colors = new Float32Array(particleCount * 3);

        const color1 = new THREE.Color(0xa855f7); // Purple
        const color2 = new THREE.Color(0x06b6d4); // Cyan
        const color3 = new THREE.Color(0x3b82f6); // Blue

        for (let i = 0; i < particleCount; i++) {
            const i3 = i * 3;
            // Distribute particles in a spherical galaxy shell
            const radius = 250 + Math.random() * 550;
            const theta = Math.random() * Math.PI * 2;
            const phi = Math.acos((Math.random() * 2) - 1);

            positions[i3] = radius * Math.sin(phi) * Math.cos(theta);
            positions[i3 + 1] = radius * Math.sin(phi) * Math.sin(theta);
            positions[i3 + 2] = radius * Math.cos(phi);

            // Mix vibrant neon colors
            const mixColor = (i % 3 === 0) ? color1 : ((i % 3 === 1) ? color2 : color3);
            colors[i3] = mixColor.r;
            colors[i3 + 1] = mixColor.g;
            colors[i3 + 2] = mixColor.b;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

        const pMaterial = new THREE.PointsMaterial({
            size: 3.5,
            vertexColors: true,
            transparent: true,
            opacity: 0.75,
            blending: THREE.AdditiveBlending
        });

        particles = new THREE.Points(geometry, pMaterial);
        scene.add(particles);

        // 7. Event listeners
        window.addEventListener('resize', onWindowResize);
        document.addEventListener('mousemove', onDocumentMouseMove);

        animate();
    }

    function onWindowResize() {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    }

    function onDocumentMouseMove(event) {
        mouseX = (event.clientX - windowHalfX) * 0.4;
        mouseY = (event.clientY - windowHalfY) * 0.4;
    }

    function animate() {
        requestAnimationFrame(animate);

        // Smooth camera follow mouse
        targetX += (mouseX - targetX) * 0.03;
        targetY += (mouseY - targetY) * 0.03;

        camera.position.x += (targetX - camera.position.x) * 0.05;
        camera.position.y += (-targetY - camera.position.y) * 0.05;
        camera.lookAt(scene.position);

        // Rotate sphere and rings
        if (sphereMesh) {
            sphereMesh.rotation.y += 0.004;
            sphereMesh.rotation.x += 0.002;
        }
        if (ringMesh) {
            ringMesh.rotation.z += 0.006;
            ringMesh.rotation.y -= 0.003;
        }
        if (particles) {
            particles.rotation.y -= 0.0015;
            particles.rotation.x += 0.0008;
        }

        renderer.render(scene, camera);
    }

    init();
});
