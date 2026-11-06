# Code quality

Graphify reports weak cohesion in several communities, including Host Management & Events and Core Framework & Routing. `HostController`, `Model`, and `EventModel` are high-connectivity abstractions and should be reviewed for responsibility concentration before refactoring proposals are made.
