describe('Automated Test - Auth API (Laravel Sanctum)', () => {
  // === CONFIG URL LANGSUNG DI SINI ===
  // Ganti port 8000 kalau Laravel kamu jalan di port lain (misal: 8080)
  const baseUrl = 'http://127.0.0.1:8000/api'; 

  // Generate data dinamis biar gak mentok di validasi 'unique' Laravel
  const randomStr = Math.random().toString(36).substring(2, 7);
  const testUser = {
    username: `user_${randomStr}`,
    email: `cypress_${randomStr}@example.com`,
    password: 'Password123',
    password_confirmation: 'Password123'
  };

  let authToken = '';

  // ==========================================
  // 1. TEST REGISTER
  // ==========================================
  it('Harus berhasil melakukan Register user baru', () => {
    cy.request({
      method: 'POST',
      url: `${baseUrl}/register`, 
      body: {
        username: testUser.username,
        email: testUser.email,
        password: testUser.password,
        password_confirmation: testUser.password_confirmation
      }
    }).then((response) => {
      expect(response.status).to.eq(200);
      expect(response.body).to.have.property('access_token');
      expect(response.body).to.have.property('token_type', 'Bearer');
      expect(response.body.data).to.have.property('username', testUser.username);
    });
  });

  it('Harus gagal Register jika validasi tidak sesuai (Skenario Error)', () => {
    cy.request({
      method: 'POST',
      url: `${baseUrl}/register`,
      failOnStatusCode: false, // Biar test gak patah saat Laravel return 422
      body: {
        username: '', // Kosong
        email: 'bukan-email', // Format salah
        password: '123', // Kurang dari 8 karakter
        password_confirmation: 'beda'
      }
    }).then((response) => {
      expect(response.status).to.eq(422);
      expect(response.body).to.have.property('username');
      expect(response.body).to.have.property('email');
      expect(response.body).to.have.property('password');
    });
  });

  // ==========================================
  // 2. TEST LOGIN
  // ==========================================
  it('Harus berhasil Login dengan akun yang sudah didaftarkan', () => {
    cy.request({
      method: 'POST',
      url: `${baseUrl}/login`,
      body: {
        email: testUser.email,
        password: testUser.password
      }
    }).then((response) => {
      expect(response.status).to.eq(200);
      expect(response.body).to.have.property('message', 'Login success');
      expect(response.body).to.have.property('access_token');
      
      // Simpan token ke variabel untuk digunakan di test berikutnya
      authToken = response.body.access_token;
    });
  });

  // ==========================================
  // 3. TEST GET PROFILE (/me) -> Butuh Auth Token
  // ==========================================
  it('Harus berhasil mengambil data profil user sendiri', () => {
    expect(authToken).to.not.be.empty;

    cy.request({
      method: 'GET',
      url: `${baseUrl}/me`,
      headers: {
        'Authorization': `Bearer ${authToken}`
      }
    }).then((response) => {
      expect(response.status).to.eq(200);
      expect(response.body).to.have.property('email', testUser.email);
      expect(response.body).to.have.property('roles');
    });
  });

  // ==========================================
  // 4. TEST LOGOUT -> Butuh Auth Token
  // ==========================================
  it('Harus berhasil Logout dan menghapus token', () => {
    cy.request({
      method: 'POST',
      url: `${baseUrl}/logout`,
      headers: {
        'Authorization': `Bearer ${authToken}`
      }
    }).then((response) => {
      expect(response.status).to.eq(200);
      expect(response.body).to.have.property('message', 'Logged out successfully');
    });
  });
});