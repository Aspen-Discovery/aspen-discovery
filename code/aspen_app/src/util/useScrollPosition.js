import { useState, useLayoutEffect } from "react";

function useScrollPosition() {
  const [scrollPosition, setScrollPosition] = useState(0);

  useLayoutEffect(() => {
    const updatePosition = () => {
      setScrollPosition(window.pageYOffset);
    };
    window.addEventListener("scroll", updatePosition);
    updatePosition();
    return () => window.removeEventListener("scroll", updatePosition);
  }, []);

  console.log(scrollPosition);
  return scrollPosition;
}

export default useScrollPosition;