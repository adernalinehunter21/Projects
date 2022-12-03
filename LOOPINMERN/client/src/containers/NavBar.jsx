import React, { Fragment } from 'react';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';

import { logout } from '../store/actions';

const Navbar = ({ auth, logout }) => (
  <nav className="navbar">
    <div className="container">
      <ul className="navbar-container">
        <li>
          <Link className="navbar-brand" to="/">
            LOOPIN
          </Link>
        </li>
        <li>
          <Link className="navbar-item" to="/about">
            About
          </Link>
        </li>
        <li>
          <Link className="navbar-item" to="/surveys">
            Survey Policy
          </Link>
        </li>
        {!auth.isAuthenticated && (
          <Fragment>
            <li>
              <Link className="navbar-item" to="/register">
                Register
              </Link>
            </li>
            <li>
              <Link className="navbar-item" to="/login">
                Login
              </Link>
            </li>
          </Fragment>
        )}
        {auth.isAuthenticated && (
          <Fragment>
            <li>
              <Link className="navbar-item" to="/poll/new">
                New Survey
              </Link>
            </li>
            <li>
              <a className="navbar-item" onClick={logout}>
                Logout
              </a>
            </li>
          </Fragment>
        )}
        <Link className="navbar-item" to="/contact">
          Contact Us
        </Link>
      </ul>
      {auth.isAuthenticated && (
        <p className="navbar-user">Logged in as {auth.user.username}</p>
      )}
    </div>
  </nav>
);

export default connect(
  store => ({
    auth: store.auth,
  }),
  { logout },
)(Navbar);